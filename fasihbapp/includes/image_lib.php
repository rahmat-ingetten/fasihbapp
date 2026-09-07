<?php
declare(strict_types=1);

/**
 * Auto-crop screenshot HP: buang status bar (atas) & tombol navigasi/gesture bar (bawah)
 * dengan mendeteksi baris pixel yang "seragam warna" (khas status bar & nav bar solid),
 * lalu resize agar tinggi = $targetHeightPx (lebar menyesuaikan rasio).
 *
 * Dibatasi crop maksimum (persen tinggi) supaya heuristik tidak "memakan" konten asli
 * kalau screenshot ternyata sudah bersih / tidak berbentuk khas HP.
 */
/**
 * Ekstrak gambar JPEG terbesar yang ter-embed di dalam file PDF (mis. screenshot
 * HP yang di-export/scan jadi PDF). Mengembalikan path file .jpg sementara, atau
 * null kalau tidak ditemukan gambar JPEG di dalam PDF tersebut.
 *
 * Native PHP, tanpa dependency (parsing byte PDF langsung) — cukup untuk kasus
 * umum: PDF hasil scan HP / export Word yang berisi 1 gambar screenshot per halaman.
 */
function extract_largest_jpeg_from_pdf(string $pdfPath): ?string
{
    $data = file_get_contents($pdfPath);
    if ($data === false) {
        return null;
    }

    $candidates = [];
    $offset = 0;
    while (($streamPos = strpos($data, 'stream', $offset)) !== false) {
        $dictStart = max(0, $streamPos - 2000);
        $dictText = substr($data, $dictStart, $streamPos - $dictStart);
        $lastObjPos = strrpos($dictText, ' obj');
        $dict = $lastObjPos !== false ? substr($dictText, $lastObjPos) : $dictText;

        $isImage = stripos($dict, '/Subtype/Image') !== false || stripos($dict, '/Subtype /Image') !== false;
        $isJpeg = stripos($dict, 'DCTDecode') !== false;

        $dataStart = $streamPos + 6;
        if (substr($data, $dataStart, 2) === "\r\n") {
            $dataStart += 2;
        } elseif (substr($data, $dataStart, 1) === "\n") {
            $dataStart += 1;
        }

        if ($isImage && $isJpeg) {
            $length = null;
            if (preg_match('/\/Length\s+(\d+)/', $dict, $m)) {
                $length = (int) $m[1];
            }
            if ($length === null) {
                $endPos = strpos($data, 'endstream', $dataStart);
                $length = $endPos !== false ? $endPos - $dataStart : null;
            }
            if ($length !== null && $length > 1000) {
                $jpegBytes = substr($data, $dataStart, $length);
                if (substr($jpegBytes, 0, 2) === "\xFF\xD8") {
                    $candidates[] = $jpegBytes;
                }
            }
        }

        $offset = $streamPos + 6;
    }

    if (empty($candidates)) {
        return null;
    }

    usort($candidates, fn($a, $b) => strlen($b) <=> strlen($a));

    $tmpPath = tempnam(sys_get_temp_dir(), 'pdfimg') . '.jpg';
    file_put_contents($tmpPath, $candidates[0]);
    return $tmpPath;
}

/**
 * Muat gambar dari file apapun formatnya, mendeteksi tipe dari ISI file
 * (bukan dari ekstensi) — lebih tahan terhadap file yang salah label
 * (misalnya screenshot WEBP yang disimpan dengan nama .png).
 */
function load_image_any_format(string $path): \GdImage
{
    $data = file_get_contents($path);
    if ($data === false || $data === '') {
        throw new RuntimeException("Gagal membaca file: $path");
    }
    $img = @imagecreatefromstring($data);
    if (!$img) {
        $info = @getimagesizefromstring($data);
        $mime = $info['mime'] ?? 'tidak diketahui';
        throw new RuntimeException("Format gambar tidak didukung (terdeteksi: $mime). Coba buka & simpan ulang screenshot ini sebagai PNG/JPG biasa.");
    }
    return $img;
}

function auto_crop_screenshot(
    string $srcPath,
    string $destPath,
    int $targetHeightPx = 480,
    float $maxTopCropRatio = 0.10,
    float $maxBottomCropRatio = 0.10
): array {
    $src = load_image_any_format($srcPath);
    $w = imagesx($src);
    $h = imagesy($src);

    $topCrop = detect_bar_height($src, $w, $h, true, (int) round($h * $maxTopCropRatio));
    $bottomCrop = detect_bar_height($src, $w, $h, false, (int) round($h * $maxBottomCropRatio));

    // Tambah sedikit buffer supaya baris transisi/anti-alias di tepi status bar
    // (blend warna, bukan solid) ikut terbuang, bukan cuma baris solidnya saja.
    $topCrop = min($topCrop + 2, (int) round($h * $maxTopCropRatio));
    $bottomCrop = min($bottomCrop + 2, (int) round($h * $maxBottomCropRatio));

    $newH = max(1, $h - $topCrop - $bottomCrop);
    $newW = $w;

    $cropped = imagecreatetruecolor($newW, $newH);
    imagecopy($cropped, $src, 0, 0, 0, $topCrop, $newW, $newH);
    imagedestroy($src);

    // Buang juga sisa ruang kosong/putih di bawah konten (setelah status bar &
    // nav bar dibuang), supaya hasil crop rapat mengikuti konten sesungguhnya —
    // bukan cuma buang "chrome" HP tapi juga whitespace kosong di layar app.
    $contentBottom = find_content_bottom($cropped, $newW, $newH);
    if ($contentBottom < $newH) {
        $paddedBottom = min($newH, $contentBottom + 14); // sisakan sedikit margin bawah
        $trimmed = imagecreatetruecolor($newW, $paddedBottom);
        imagecopy($trimmed, $cropped, 0, 0, 0, 0, $newW, $paddedBottom);
        imagedestroy($cropped);
        $cropped = $trimmed;
        $newH = $paddedBottom;
    }

    // Resize proporsional ke targetHeightPx
    $scale = $targetHeightPx / $newH;
    $finalW = max(1, (int) round($newW * $scale));
    $finalH = $targetHeightPx;

    $final = imagecreatetruecolor($finalW, $finalH);
    imagecopyresampled($final, $cropped, 0, 0, 0, 0, $finalW, $finalH, $newW, $newH);
    imagedestroy($cropped);

    ensureDir(dirname($destPath));
    imagepng($final, $destPath);
    imagedestroy($final);

    return [$finalW, $finalH];
}

/**
 * Deteksi tinggi "bar" solid (status bar / nav bar) dari tepi atas atau bawah gambar,
 * dengan menyusuri baris demi baris selama variasi warna baris tsb rendah (uniform),
 * dan warnanya konsisten dengan baris paling tepi.
 */
function detect_bar_height(\GdImage $img, int $w, int $h, bool $fromTop, int $maxCrop): int
{
    if ($maxCrop <= 0) {
        return 0;
    }
    $sampleCols = min(40, $w);
    $step = max(1, intdiv($w, $sampleCols));

    $edgeY = $fromTop ? 0 : $h - 1;
    $edgeColor = row_median_color($img, $edgeY, $w, $step);

    $barHeight = 0;
    $misses = 0;
    for ($i = 0; $i < $maxCrop; $i++) {
        $y = $fromTop ? $i : ($h - 1 - $i);
        if ($y < 0 || $y >= $h) {
            break;
        }
        $rowColor = row_median_color($img, $y, $w, $step);
        $diffFromEdge = color_distance($rowColor, $edgeColor);

        // Baris dianggap bagian dari bar solid jika warna MEDIAN-nya (tahan terhadap
        // beberapa pixel icon/teks kecil di dalamnya) masih dekat dengan warna tepi.
        if ($diffFromEdge < 45) {
            $barHeight = $i + 1;
            $misses = 0;
        } else {
            // toleransi 1-2 baris transisi/anti-aliasing sebelum benar-benar berhenti
            $misses++;
            if ($misses > 2) {
                break;
            }
        }
    }
    return $barHeight;
}

function row_median_color(\GdImage $img, int $y, int $w, int $step): array
{
    $rs = $gs = $bs = [];
    for ($x = 0; $x < $w; $x += $step) {
        $rgb = imagecolorat($img, $x, $y);
        $colors = imagecolorsforindex($img, $rgb);
        $rs[] = $colors['red'];
        $gs[] = $colors['green'];
        $bs[] = $colors['blue'];
    }
    sort($rs); sort($gs); sort($bs);
    $mid = intdiv(count($rs), 2);
    return [$rs[$mid] ?? 0, $gs[$mid] ?? 0, $bs[$mid] ?? 0];
}

function row_color_variance(\GdImage $img, int $y, int $w, int $step): float
{
    $pixels = [];
    for ($x = 0; $x < $w; $x += $step) {
        $rgb = imagecolorat($img, $x, $y);
        $colors = imagecolorsforindex($img, $rgb);
        $pixels[] = $colors;
    }
    $n = count($pixels);
    if ($n === 0) {
        return 0;
    }
    $avgR = array_sum(array_column($pixels, 'red')) / $n;
    $avgG = array_sum(array_column($pixels, 'green')) / $n;
    $avgB = array_sum(array_column($pixels, 'blue')) / $n;
    $variance = 0;
    foreach ($pixels as $c) {
        $variance += ($c['red'] - $avgR) ** 2 + ($c['green'] - $avgG) ** 2 + ($c['blue'] - $avgB) ** 2;
    }
    return $variance / $n;
}

function color_distance(array $a, array $b): float
{
    return sqrt((($a[0] - $b[0]) ** 2) + (($a[1] - $b[1]) ** 2) + (($a[2] - $b[2]) ** 2));
}

/**
 * Cari baris terakhir (dari atas) yang masih mengandung "konten nyata"
 * (bukan cuma latar putih/kosong), dengan menyusuri dari bawah ke atas.
 * Latar belakang dianggap warna paling umum di area dekat tepi bawah gambar.
 */
function find_content_bottom(\GdImage $img, int $w, int $h): int
{
    if ($h <= 30) {
        return $h;
    }
    $sampleCols = min(30, $w);
    $step = max(1, intdiv($w, $sampleCols));
    $white = [255.0, 255.0, 255.0];

    // Tahap 1: telusuri seluruh gambar, kelompokkan baris ber-konten jadi
    // "blok-blok" (dipisah gap kecil <= 4px dianggap masih 1 blok yang sama,
    // seperti jarak antar baris teks dalam 1 paragraf).
    $blocks = []; // masing-masing: [start, end]
    $inBlock = false;
    $blockStart = 0;
    $gapRun = 0;
    for ($y = 0; $y < $h; $y++) {
        $hasContent = row_count_pixels_differing($img, $y, $w, $step, $white, 35.0) >= 2;
        if ($hasContent) {
            if (!$inBlock) {
                $blockStart = $y;
                $inBlock = true;
            }
            $gapRun = 0;
        } elseif ($inBlock) {
            $gapRun++;
            if ($gapRun > 4) {
                $blocks[] = [$blockStart, $y - $gapRun];
                $inBlock = false;
            }
        }
    }
    if ($inBlock) {
        $blocks[] = [$blockStart, $h - 1];
    }
    if (empty($blocks)) {
        return $h;
    }

    // Tahap 2: gabungkan blok-blok yang jaraknya dekat (< 12% tinggi gambar)
    // jadi satu "grup konten" — supaya elemen dalam 1 kartu/section yang
    // renggang (header, judul, tabel) tetap dianggap 1 kesatuan.
    $mergeGapThreshold = max(20, (int) round($h * 0.12));
    $groups = [[$blocks[0][0], $blocks[0][1]]];
    for ($i = 1; $i < count($blocks); $i++) {
        $gap = $blocks[$i][0] - end($groups)[1];
        if ($gap <= $mergeGapThreshold) {
            $groups[count($groups) - 1][1] = $blocks[$i][1];
        } else {
            $groups[] = [$blocks[$i][0], $blocks[$i][1]];
        }
    }

    // Tahap 3: buang grup TERAKHIR kalau dia "kecil & terisolasi" — tinggi
    // grup itu sendiri kecil (< 10% tinggi gambar) DAN jarak dari grup
    // sebelumnya besar (> 20% tinggi gambar). Ini pola khas elemen melayang
    // (tombol chat/bantuan) yang terpisah jauh dari konten utama.
    while (count($groups) > 1) {
        $last = end($groups);
        $prevEnd = $groups[count($groups) - 2][1];
        $gapBefore = $last[0] - $prevEnd;
        $groupHeight = $last[1] - $last[0];
        $isSmall = $groupHeight < max(15, (int) round($h * 0.10));
        $isFarGap = $gapBefore > max(40, (int) round($h * 0.20));
        if ($isSmall && $isFarGap) {
            array_pop($groups);
        } else {
            break;
        }
    }

    return end($groups)[1] + 1;
}

function row_count_pixels_differing(\GdImage $img, int $y, int $w, int $step, array $refColor, float $threshold): int
{
    $count = 0;
    for ($x = 0; $x < $w; $x += $step) {
        $rgb = imagecolorat($img, $x, $y);
        $c = imagecolorsforindex($img, $rgb);
        $diff = color_distance([$c['red'], $c['green'], $c['blue']], $refColor);
        if ($diff > $threshold) {
            $count++;
        }
    }
    return $count;
}
