<?php
declare(strict_types=1);
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/docx_lib.php';
require __DIR__ . '/../includes/image_lib.php';

set_time_limit(600);

$pdo = db();
$jobId = (int) ($_POST['job_id'] ?? 0);
$job = requireJob($pdo, $jobId);

$docxFiles = $pdo->prepare('SELECT * FROM docx_files WHERE job_id = ? ORDER BY id');
$docxFiles->execute([$jobId]);
$docxFiles = $docxFiles->fetchAll(PDO::FETCH_ASSOC);

if (empty($docxFiles)) {
    flash('error', 'Belum ada file Word yang diunggah.');
    redirect('../job.php?id=' . $jobId);
}

$outputDir = jobStoragePath($jobId, 'output');
$cropCacheDir = jobStoragePath($jobId, 'cache/cropped');
ensureDir($outputDir);
ensureDir($cropCacheDir);

// Pengaman: kalau belum ada SATU PUN nama yang tercocokkan dengan screenshot,
// besar kemungkinan user lupa klik "Cocokkan Otomatis" di langkah 3.
$totalMatched = $pdo->prepare("SELECT COUNT(*) FROM persons WHERE job_id = ? AND screenshot_id IS NOT NULL");
$totalMatched->execute([$jobId]);
if ((int) $totalMatched->fetchColumn() === 0) {
    flash('error', 'Belum ada satu pun nama yang cocok dengan screenshot. Klik "🔄 Cocokkan Otomatis" di bagian 3. Matching terlebih dahulu, baru klik Process All.');
    redirect('../job.php?id=' . $jobId);
}

$processed = 0;
$failed = 0;

foreach ($docxFiles as $docx) {
    $docxFileId = (int) $docx['id'];

    $personsStmt = $pdo->prepare('
        SELECT p.*, s.stored_path AS shot_path, s.id AS shot_id
        FROM persons p
        LEFT JOIN screenshots s ON s.id = p.screenshot_id
        WHERE p.docx_file_id = ?
        ORDER BY p.order_index
    ');
    $personsStmt->execute([$docxFileId]);
    $persons = $personsStmt->fetchAll(PDO::FETCH_ASSOC);

    $orderedImagePaths = [];
    $skippedNoMatch = [];
    $skippedCropError = [];
    foreach ($persons as $p) {
        if (empty($p['shot_path']) || !is_file($p['shot_path'])) {
            $orderedImagePaths[] = null;
            $skippedNoMatch[] = $p['name'];
            continue;
        }
        $cropDest = $cropCacheDir . '/person_' . $p['id'] . '_shot_' . $p['shot_id'] . '.png';
        try {
            if (!is_file($cropDest)) {
                auto_crop_screenshot($p['shot_path'], $cropDest, 480);
            }
            $orderedImagePaths[] = $cropDest;
        } catch (Throwable $e) {
            $orderedImagePaths[] = null;
            $skippedCropError[] = $p['name'] . ' (' . $e->getMessage() . ')';
        }
    }

    $baseName = pathinfo($docx['original_name'], PATHINFO_FILENAME);
    $outPath = $outputDir . '/' . preg_replace('/[^A-Za-z0-9._-]/', '_', $baseName) . '_hasil.docx';

    try {
        docx_insert_images_at_markers($docx['stored_path'], $outPath, $orderedImagePaths);

        $filledCount = count(array_filter($orderedImagePaths));
        $stmtUpd = $pdo->prepare("UPDATE docx_files SET status = 'processed' WHERE id = ?");
        $stmtUpd->execute([$docxFileId]);

        $msgParts = ["$filledCount / " . count($persons) . " screenshot tersisip"];
        if (!empty($skippedNoMatch)) {
            $msgParts[] = 'Belum ada screenshot untuk: ' . implode(', ', $skippedNoMatch);
        }
        if (!empty($skippedCropError)) {
            $msgParts[] = 'Gagal memproses gambar untuk: ' . implode(', ', $skippedCropError);
        }
        $message = implode(' — ', $msgParts);

        $log = $pdo->prepare('INSERT INTO process_log (docx_file_id, status, message, output_path, updated_at) VALUES (?, ?, ?, ?, ?)');
        $log->execute([$docxFileId, 'success', $message, $outPath, date('Y-m-d H:i:s')]);

        $processed++;
    } catch (Throwable $e) {
        $stmtUpd = $pdo->prepare("UPDATE docx_files SET status = 'error' WHERE id = ?");
        $stmtUpd->execute([$docxFileId]);
        $log = $pdo->prepare('INSERT INTO process_log (docx_file_id, status, message, output_path, updated_at) VALUES (?, ?, ?, ?, ?)');
        $log->execute([$docxFileId, 'error', $e->getMessage(), null, date('Y-m-d H:i:s')]);
        $failed++;
    }
}

if ($processed > 0) {
    flash('success', "$processed dokumen berhasil diproses.");
}
if ($failed > 0) {
    flash('error', "$failed dokumen gagal diproses.");
}

redirect('../job.php?id=' . $jobId);
