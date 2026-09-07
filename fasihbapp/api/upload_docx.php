<?php
declare(strict_types=1);
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/docx_lib.php';

$pdo = db();
$jobId = (int) ($_POST['job_id'] ?? 0);
$job = requireJob($pdo, $jobId);

if (empty($_FILES['docx_files']['name'][0] ?? null)) {
    flash('error', 'Pilih minimal satu file Word (.docx) untuk diunggah.');
    redirect('../job.php?id=' . $jobId);
}

$destDir = jobStoragePath($jobId, 'uploads/docx');
ensureDir($destDir);

$okCount = 0;
$errCount = 0;

$names = $_FILES['docx_files']['name'];
$tmpNames = $_FILES['docx_files']['tmp_name'];
$errors = $_FILES['docx_files']['error'];

for ($i = 0; $i < count($names); $i++) {
    if ($errors[$i] !== UPLOAD_ERR_OK) {
        $errCount++;
        continue;
    }
    $origName = $names[$i];
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if ($ext !== 'docx') {
        flash('error', "Dilewati (bukan .docx): " . h($origName));
        $errCount++;
        continue;
    }

    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $origName);
    $storedPath = $destDir . '/' . uniqid() . '_' . $safeName;
    if (!move_uploaded_file($tmpNames[$i], $storedPath)) {
        $errCount++;
        continue;
    }

    try {
        $extracted = docx_extract_persons($storedPath);
    } catch (Throwable $e) {
        flash('error', "Gagal membaca $origName: " . $e->getMessage());
        unlink($storedPath);
        $errCount++;
        continue;
    }

    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO docx_files (job_id, original_name, stored_path, person_count, marker_count, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $jobId, $origName, $storedPath,
        count($extracted['persons']), $extracted['marker_count'],
        'uploaded', date('Y-m-d H:i:s'),
    ]);
    $docxFileId = (int) $pdo->lastInsertId();

    $insPerson = $pdo->prepare('INSERT INTO persons (job_id, docx_file_id, order_index, name) VALUES (?, ?, ?, ?)');
    foreach ($extracted['persons'] as $idx => $name) {
        $insPerson->execute([$jobId, $docxFileId, $idx, $name]);
    }
    $pdo->commit();

    if (count($extracted['persons']) !== $extracted['marker_count']) {
        flash('info', "$origName: ditemukan " . count($extracted['persons']) . " nama tapi " . $extracted['marker_count'] . " lokasi screenshot — periksa kembali dokumen ini.");
    }

    $okCount++;
}

if ($okCount > 0) {
    flash('success', "$okCount file Word berhasil diunggah & dibaca.");
}
if ($errCount > 0) {
    flash('error', "$errCount file gagal diproses.");
}

redirect('../job.php?id=' . $jobId);
