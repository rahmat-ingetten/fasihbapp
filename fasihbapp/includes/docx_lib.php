<?php
declare(strict_types=1);

/**
 * Library untuk membaca & menulis file .docx secara langsung (manipulasi XML),
 * tanpa dependency Composer/PHPWord.
 *
 * Pola dokumen yang didukung (BERITA ACARA Sensus Ekonomi):
 *  - Nama "Petugas Lapangan" (PIHAK KEDUA) ditandai paragraf "<n>. Nama : <NAMA>"
 *    yang SEGERA diikuti paragraf berisi "NIK :" (pembeda dari nama supervisor
 *    tetap yang diikuti "NIP :").
 *  - Lokasi sisip screenshot ditandai paragraf berisi teks "Screenshoot Aplikasi Fasih"
 *    (atau varian ejaan "Screenshot"), satu marker per orang, urut sesuai urutan nama.
 */

const WORDML_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
const RELS_NS    = 'http://schemas.openxmlformats.org/package/2006/relationships';
const DRAWING_NS = 'http://schemas.openxmlformats.org/drawingml/2006/main';
const WP_NS      = 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing';
const A_NS       = 'http://schemas.openxmlformats.org/drawingml/2006/main';
const PIC_NS     = 'http://schemas.openxmlformats.org/drawingml/2006/picture';
const R_NS       = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
const CT_NS      = 'http://schemas.openxmlformats.org/package/2006/content-types';

/** Ambil teks gabungan semua <w:t> di dalam satu <w:p>, tahan terhadap run yang terpecah. */
/** Ambil nilai <w:ind w:left="..."> dari paragraf (dalam twips), dikonversi ke EMU. */
function getIndentEmu(DOMElement $p): int
{
    $indList = $p->getElementsByTagNameNS(WORDML_NS, 'ind');
    if ($indList->length === 0) {
        return 228600; // default ~0.25 inch, dipakai kalau paragraf tak punya indentasi eksplisit
    }
    $left = $indList->item(0)->getAttribute('w:left');
    if ($left === '') {
        return 228600;
    }
    return (int) round(((int) $left) * 635); // 1 twip = 635 EMU
}

/**
 * Ambil <w:ind> dari sebuah paragraf (kalau ada), untuk disalin ke paragraf baru
 * supaya indentasi konsisten dengan paragraf marker aslinya.
 */
function clonePPrIndent(DOMDocument $dom, DOMElement $sourceP): ?DOMElement
{
    $pPrList = $sourceP->getElementsByTagNameNS(WORDML_NS, 'pPr');
    if ($pPrList->length === 0) {
        return null;
    }
    $indList = $pPrList->item(0)->getElementsByTagNameNS(WORDML_NS, 'ind');
    if ($indList->length === 0) {
        return null;
    }
    return $indList->item(0)->cloneNode(true);
}

/**
 * Bangun <w:pPr> "bersih": tanpa border sama sekali (mencegah garis yang
 * ter-inherit dari style dokumen), dengan indentasi mengikuti paragraf marker.
 */
function buildResetPPr(DOMDocument $dom, DOMElement $markerP): DOMElement
{
    $pPr = $dom->createElementNS(WORDML_NS, 'w:pPr');

    $ind = clonePPrIndent($dom, $markerP);
    if ($ind) {
        $pPr->appendChild($ind);
    }

    $pBdr = $dom->createElementNS(WORDML_NS, 'w:pBdr');
    foreach (['top', 'left', 'bottom', 'right', 'between'] as $side) {
        $border = $dom->createElementNS(WORDML_NS, 'w:' . $side);
        $border->setAttribute('w:val', 'nil');
        $pBdr->appendChild($border);
    }
    $pPr->appendChild($pBdr);

    return $pPr;
}

/** Cek apakah teks paragraf kosong secara visual (termasuk toleran spasi non-breaking). */
function isBlankParagraphText(string $text): bool
{
    $normalized = str_replace("\xC2\xA0", ' ', $text); // NBSP -> spasi biasa
    return trim($normalized) === '';
}

function docx_paragraph_text(DOMElement $p): string
{
    $texts = $p->getElementsByTagNameNS(WORDML_NS, 't');
    $out = '';
    foreach ($texts as $t) {
        $out .= $t->textContent;
    }
    return $out;
}

/** Buka document.xml dari sebuah .docx dan kembalikan [DOMDocument, DOMXPath]. */
function docx_load_document_xml(string $docxPath): array
{
    $zip = new ZipArchive();
    if ($zip->open($docxPath) !== true) {
        throw new RuntimeException("Gagal membuka file docx: $docxPath");
    }
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    if ($xml === false) {
        throw new RuntimeException("File docx tidak valid (word/document.xml tidak ditemukan)");
    }

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = true;
    $dom->loadXML($xml);

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', WORDML_NS);

    return [$dom, $xpath];
}

/**
 * Ekstrak daftar nama orang (Petugas Lapangan / PIHAK KEDUA) secara berurutan,
 * dan hitung jumlah marker "Screenshoot Aplikasi Fasih".
 *
 * @return array{persons: array<int,string>, marker_count: int}
 */
function docx_extract_persons(string $docxPath): array
{
    [$dom, $xpath] = docx_load_document_xml($docxPath);

    $paragraphs = $xpath->query('//w:p');
    $paraTexts = [];
    foreach ($paragraphs as $p) {
        $paraTexts[] = docx_paragraph_text($p);
    }

    $persons = [];
    $n = count($paraTexts);
    for ($i = 0; $i < $n; $i++) {
        $text = trim($paraTexts[$i]);
        // Pola: "2. Nama : Hanna Kristiani Br Ginting" (nomor & spasi bisa bervariasi)
        if (preg_match('/^\s*\d+[.\)]?\s*Nama\s*:\s*(.+)$/ui', $text, $m)) {
            $name = trim($m[1]);
            if ($name === '') {
                continue;
            }
            // Cari pembeda: 1-2 paragraf berikutnya harus mengandung "NIK" (bukan "NIP")
            $isPetugasLapangan = false;
            for ($j = $i + 1; $j <= min($i + 2, $n - 1); $j++) {
                $next = $paraTexts[$j];
                if (stripos($next, 'NIK') !== false) {
                    $isPetugasLapangan = true;
                    break;
                }
                if (stripos($next, 'NIP') !== false) {
                    break; // ini nama supervisor tetap, bukan target
                }
            }
            if ($isPetugasLapangan) {
                $persons[] = $name;
            }
        }
    }

    $markerCount = 0;
    foreach ($paraTexts as $text) {
        if (preg_match('/screenshoo?t\s+aplikasi\s+fasih/ui', $text)) {
            $markerCount++;
        }
    }

    return ['persons' => $persons, 'marker_count' => $markerCount];
}

/**
 * Sisipkan gambar-gambar ke dalam docx tepat setelah tiap marker
 * "Screenshoot Aplikasi Fasih", urut sesuai urutan marker.
 *
 * @param string $srcDocxPath   Path docx asli (tidak diubah)
 * @param string $destDocxPath  Path output docx baru
 * @param array<int,?string> $orderedImagePaths  Index 0..N-1 sesuai urutan marker ke-1..N.
 *        Nilai null berarti marker tsb dilewati (tidak ada screenshot).
 * @param int $imageHeightEmu   Tinggi gambar target dalam EMU (lebar menyesuaikan rasio).
 */
function docx_insert_images_at_markers(
    string $srcDocxPath,
    string $destDocxPath,
    array $orderedImagePaths,
    int $imageHeightEmu = 2376000
): void {
    if (!copy($srcDocxPath, $destDocxPath)) {
        throw new RuntimeException("Gagal menyalin docx ke output: $destDocxPath");
    }

    $zip = new ZipArchive();
    if ($zip->open($destDocxPath) !== true) {
        throw new RuntimeException("Gagal membuka output docx: $destDocxPath");
    }

    $documentXml = $zip->getFromName('word/document.xml');
    $relsXml = $zip->getFromName('word/_rels/document.xml.rels');
    $contentTypesXml = $zip->getFromName('[Content_Types].xml');
    if ($documentXml === false || $relsXml === false || $contentTypesXml === false) {
        $zip->close();
        throw new RuntimeException('Struktur docx tidak lengkap.');
    }

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = true;
    $dom->loadXML($documentXml);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', WORDML_NS);

    $relsDom = new DOMDocument();
    $relsDom->preserveWhiteSpace = true;
    $relsDom->loadXML($relsXml);

    $ctDom = new DOMDocument();
    $ctDom->preserveWhiteSpace = true;
    $ctDom->loadXML($contentTypesXml);

    // Tentukan rId berikutnya yang aman dipakai
    $maxRid = 0;
    foreach ($relsDom->getElementsByTagNameNS(RELS_NS, 'Relationship') as $rel) {
        $id = $rel->getAttribute('Id');
        if (preg_match('/^rId(\d+)$/', $id, $m)) {
            $maxRid = max($maxRid, (int) $m[1]);
        }
    }

    // Tentukan nomor image berikutnya (image1.png, image2.png, ...) dari daftar file zip
    $maxImgNum = 0;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (preg_match('#word/media/image(\d+)\.\w+$#', $name, $m)) {
            $maxImgNum = max($maxImgNum, (int) $m[1]);
        }
    }

    // Pastikan Content Types punya default untuk png/jpg/jpeg
    ensureContentTypeDefault($ctDom, 'png', 'image/png');
    ensureContentTypeDefault($ctDom, 'jpeg', 'image/jpeg');
    ensureContentTypeDefault($ctDom, 'jpg', 'image/jpeg');

    // Kumpulkan paragraf marker sesuai urutan kemunculan
    $paragraphs = $xpath->query('//w:p');
    $markerNodes = [];
    foreach ($paragraphs as $p) {
        $text = docx_paragraph_text($p);
        if (preg_match('/screenshoo?t\s+aplikasi\s+fasih/ui', $text)) {
            $markerNodes[] = $p;
        }
    }

    $drawingId = 1000; // docPr id, cukup mulai dari angka besar agar tidak bentrok
    $newMediaFiles = []; // ['path/in/zip' => 'source file on disk']

    foreach ($markerNodes as $idx => $markerP) {
        $imgPath = $orderedImagePaths[$idx] ?? null;
        if (!$imgPath || !is_file($imgPath)) {
            continue;
        }

        [$pxW, $pxH] = getimagesize($imgPath) ?: [0, 0];
        if ($pxW <= 0 || $pxH <= 0) {
            continue;
        }
        $emuH = $imageHeightEmu;
        $emuW = (int) round($imageHeightEmu * ($pxW / $pxH));

        $maxImgNum++;
        $ext = strtolower(pathinfo($imgPath, PATHINFO_EXTENSION)) ?: 'png';
        if ($ext === 'jpg') {
            $ext = 'jpeg';
        }
        $mediaName = "image{$maxImgNum}.{$ext}";
        $mediaZipPath = "word/media/{$mediaName}";
        $newMediaFiles[$mediaZipPath] = $imgPath;

        $maxRid++;
        $rId = "rId{$maxRid}";
        addRelationship($relsDom, $rId, 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image', "media/{$mediaName}");

        $drawingId++;

        // Supaya ada 1 baris jarak sebelum gambar, pakai baris kosong yang SUDAH
        // ada di template kalau tersedia (tidak menambah panjang dokumen). Kalau
        // template tidak punya cukup baris kosong siap pakai, baru tambah paragraf
        // baru sebagai cadangan (supaya jarak tetap selalu ada di kasus apapun).
        $spacerCandidate = $markerP->nextSibling;
        while ($spacerCandidate && $spacerCandidate->nodeType !== XML_ELEMENT_NODE) {
            $spacerCandidate = $spacerCandidate->nextSibling;
        }
        $spacerIsBlank = $spacerCandidate && isBlankParagraphText(docx_paragraph_text($spacerCandidate))
            && $spacerCandidate->getElementsByTagNameNS(WORDML_NS, 'drawing')->length === 0;

        $imageHost = null;
        if ($spacerIsBlank) {
            $candidate2 = $spacerCandidate->nextSibling;
            while ($candidate2 && $candidate2->nodeType !== XML_ELEMENT_NODE) {
                $candidate2 = $candidate2->nextSibling;
            }
            if ($candidate2
                && isBlankParagraphText(docx_paragraph_text($candidate2))
                && $candidate2->getElementsByTagNameNS(WORDML_NS, 'drawing')->length === 0) {
                $imageHost = $candidate2; // baris kosong ke-2 di template, dipakai utk gambar
            }
        }

        if ($imageHost) {
            // Pakai paragraf kosong yang sudah ada: bersihkan isinya, tambahkan run gambar.
            while ($imageHost->firstChild) {
                $imageHost->removeChild($imageHost->firstChild);
            }
            $imageHost->appendChild(buildResetPPr($dom, $markerP));
            $runAndDrawing = buildImageParagraph($dom, $rId, $emuW, $emuH, $drawingId, "Screenshot Fasih", getIndentEmu($markerP));
            foreach (iterator_to_array($runAndDrawing->childNodes) as $child) {
                if ($child->nodeName !== 'w:pPr') {
                    $imageHost->appendChild($child);
                }
            }
        } else {
            // Cadangan: template tidak punya 2 baris kosong siap pakai berturut-turut.
            // Tambah paragraf baru untuk jarak (kalau belum ada satupun baris kosong),
            // lalu paragraf baru lagi untuk gambar — supaya jarak 1 baris tetap terjamin.
            $newParagraph = buildImageParagraph($dom, $rId, $emuW, $emuH, $drawingId, "Screenshot Fasih", getIndentEmu($markerP));
            $newParagraph->insertBefore(buildResetPPr($dom, $markerP), $newParagraph->firstChild);

            if ($spacerIsBlank) {
                // Sudah ada 1 baris kosong (dipakai sbg jarak) — taruh gambar tepat setelahnya.
                $spacerCandidate->parentNode->insertBefore($newParagraph, $spacerCandidate->nextSibling);
            } else {
                // Tidak ada baris kosong sama sekali setelah marker — buat spacer baru + gambar.
                $spacerParagraph = $dom->createElementNS(WORDML_NS, 'w:p');
                $spacerParagraph->appendChild(buildResetPPr($dom, $markerP));
                $markerP->parentNode->insertBefore($spacerParagraph, $markerP->nextSibling);
                $spacerParagraph->parentNode->insertBefore($newParagraph, $spacerParagraph->nextSibling);
            }
        }
    }

    // Tulis balik semua perubahan ke zip
    $zip->addFromString('word/document.xml', $dom->saveXML());
    $zip->addFromString('word/_rels/document.xml.rels', $relsDom->saveXML());
    $zip->addFromString('[Content_Types].xml', $ctDom->saveXML());
    foreach ($newMediaFiles as $zipPath => $diskPath) {
        $zip->addFile($diskPath, $zipPath);
    }

    $zip->close();
}

function ensureContentTypeDefault(DOMDocument $ctDom, string $extension, string $contentType): void
{
    $xpath = new DOMXPath($ctDom);
    $xpath->registerNamespace('ct', CT_NS);
    $existing = $xpath->query("//ct:Default[@Extension='{$extension}']");
    if ($existing->length > 0) {
        return;
    }
    $root = $ctDom->documentElement;
    $default = $ctDom->createElementNS(CT_NS, 'Default');
    $default->setAttribute('Extension', $extension);
    $default->setAttribute('ContentType', $contentType);
    $root->insertBefore($default, $root->firstChild);
}

function addRelationship(DOMDocument $relsDom, string $id, string $type, string $target): void
{
    $root = $relsDom->documentElement;
    $rel = $relsDom->createElementNS(RELS_NS, 'Relationship');
    $rel->setAttribute('Id', $id);
    $rel->setAttribute('Type', $type);
    $rel->setAttribute('Target', $target);
    $root->appendChild($rel);
}

/** Bangun satu <w:p> baru berisi <w:drawing> gambar ANCHORED ("In Front of Text") —
 *  gambar melayang di posisi tetap, tidak mendorong paragraf lain, memanfaatkan
 *  baris-baris kosong yang sudah disediakan template sebagai ruang kosongnya. */
function buildImageParagraph(DOMDocument $dom, string $rId, int $emuW, int $emuH, int $drawingId, string $name, int $offsetHEmu = 228600): DOMElement
{
    $p = $dom->createElementNS(WORDML_NS, 'w:p');

    $r = $dom->createElementNS(WORDML_NS, 'w:r');
    $rPr = $dom->createElementNS(WORDML_NS, 'w:rPr');
    $rPr->appendChild($dom->createElementNS(WORDML_NS, 'w:noProof'));
    $r->appendChild($rPr);

    $drawing = $dom->createElementNS(WORDML_NS, 'w:drawing');

    $anchor = $dom->createElementNS(WP_NS, 'wp:anchor');
    $anchor->setAttribute('distT', '0');
    $anchor->setAttribute('distB', '0');
    $anchor->setAttribute('distL', '114300');
    $anchor->setAttribute('distR', '114300');
    $anchor->setAttribute('simplePos', '0');
    $anchor->setAttribute('relativeHeight', (string) (251658240 + $drawingId));
    $anchor->setAttribute('behindDoc', '0');   // 0 = di depan teks (in front of text)
    $anchor->setAttribute('locked', '0');
    $anchor->setAttribute('layoutInCell', '1');
    $anchor->setAttribute('allowOverlap', '1');

    $simplePos = $dom->createElementNS(WP_NS, 'wp:simplePos');
    $simplePos->setAttribute('x', '0');
    $simplePos->setAttribute('y', '0');
    $anchor->appendChild($simplePos);

    $posH = $dom->createElementNS(WP_NS, 'wp:positionH');
    $posH->setAttribute('relativeFrom', 'column');
    $posHOffset = $dom->createElementNS(WP_NS, 'wp:posOffset');
    $posHOffset->textContent = (string) $offsetHEmu;
    $posH->appendChild($posHOffset);
    $anchor->appendChild($posH);

    $posV = $dom->createElementNS(WP_NS, 'wp:positionV');
    $posV->setAttribute('relativeFrom', 'paragraph');
    $posVOffset = $dom->createElementNS(WP_NS, 'wp:posOffset');
    $posVOffset->textContent = '0';
    $posV->appendChild($posVOffset);
    $anchor->appendChild($posV);

    $extent = $dom->createElementNS(WP_NS, 'wp:extent');
    $extent->setAttribute('cx', (string) $emuW);
    $extent->setAttribute('cy', (string) $emuH);
    $anchor->appendChild($extent);

    $effectExtent = $dom->createElementNS(WP_NS, 'wp:effectExtent');
    $effectExtent->setAttribute('l', '0');
    $effectExtent->setAttribute('t', '0');
    $effectExtent->setAttribute('r', '1905');
    $effectExtent->setAttribute('b', '5715');
    $anchor->appendChild($effectExtent);

    $wrapNone = $dom->createElementNS(WP_NS, 'wp:wrapNone');
    $anchor->appendChild($wrapNone);

    $docPr = $dom->createElementNS(WP_NS, 'wp:docPr');
    $docPr->setAttribute('id', (string) $drawingId);
    $docPr->setAttribute('name', $name . ' ' . $drawingId);
    $anchor->appendChild($docPr);

    $cNvGraphicFramePr = $dom->createElementNS(WP_NS, 'wp:cNvGraphicFramePr');
    $graphicFrameLocks = $dom->createElementNS(A_NS, 'a:graphicFrameLocks');
    $graphicFrameLocks->setAttribute('noChangeAspect', '1');
    $cNvGraphicFramePr->appendChild($graphicFrameLocks);
    $anchor->appendChild($cNvGraphicFramePr);

    $graphic = $dom->createElementNS(A_NS, 'a:graphic');
    $graphicData = $dom->createElementNS(A_NS, 'a:graphicData');
    $graphicData->setAttribute('uri', PIC_NS);

    $pic = $dom->createElementNS(PIC_NS, 'pic:pic');

    $nvPicPr = $dom->createElementNS(PIC_NS, 'pic:nvPicPr');
    $cNvPr = $dom->createElementNS(PIC_NS, 'pic:cNvPr');
    $cNvPr->setAttribute('id', (string) $drawingId);
    $cNvPr->setAttribute('name', $name);
    $nvPicPr->appendChild($cNvPr);
    $cNvPicPr = $dom->createElementNS(PIC_NS, 'pic:cNvPicPr');
    $nvPicPr->appendChild($cNvPicPr);
    $pic->appendChild($nvPicPr);

    $blipFill = $dom->createElementNS(PIC_NS, 'pic:blipFill');
    $blip = $dom->createElementNS(A_NS, 'a:blip');
    $blip->setAttributeNS(R_NS, 'r:embed', $rId);
    $blipFill->appendChild($blip);
    $stretch = $dom->createElementNS(A_NS, 'a:stretch');
    $fillRect = $dom->createElementNS(A_NS, 'a:fillRect');
    $stretch->appendChild($fillRect);
    $blipFill->appendChild($stretch);
    $pic->appendChild($blipFill);

    $spPr = $dom->createElementNS(PIC_NS, 'pic:spPr');
    $xfrm = $dom->createElementNS(A_NS, 'a:xfrm');
    $off = $dom->createElementNS(A_NS, 'a:off');
    $off->setAttribute('x', '0');
    $off->setAttribute('y', '0');
    $xfrm->appendChild($off);
    $ext2 = $dom->createElementNS(A_NS, 'a:ext');
    $ext2->setAttribute('cx', (string) $emuW);
    $ext2->setAttribute('cy', (string) $emuH);
    $xfrm->appendChild($ext2);
    $spPr->appendChild($xfrm);
    $prstGeom = $dom->createElementNS(A_NS, 'a:prstGeom');
    $prstGeom->setAttribute('prst', 'rect');
    $avLst = $dom->createElementNS(A_NS, 'a:avLst');
    $prstGeom->appendChild($avLst);
    $spPr->appendChild($prstGeom);
    $pic->appendChild($spPr);

    $graphicData->appendChild($pic);
    $graphic->appendChild($graphicData);
    $anchor->appendChild($graphic);
    $drawing->appendChild($anchor);
    $r->appendChild($drawing);
    $p->appendChild($r);

    return $p;
}
