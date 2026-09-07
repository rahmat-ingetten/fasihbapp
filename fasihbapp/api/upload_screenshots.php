<?php
declare(strict_types=1);
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/image_lib.php';

$pdo = db();
$jobId = (int) ($_POST['job_id'] ?? 0);
$job = requireJob($pdo, $jobId);

if (empty($_FILES['screenshots']['name'][0] ?? null)) {
    flash('error', 'Pilih minimal satu file screenshot untuk diunggah.');
    redirect('../job.php?id=' . $jobId);
}

$destDir = jobStoragePath($jobId, 'uploads/screenshots');
ensureDir($destDir);

$allowedExt = ['png', 'jpg', 'jpeg', 'webp', 'pdf'];
$okCount = 0;
$errCount = 0;
$pdfCount = 0;

$names = $_FILES['screenshots']['name'];
$tmpNames = $_FILES['screenshots']['tmp_name'];
$errors = $_FILES['screenshots']['error'];

$stmt = $pdo->prepare('INSERT INTO screenshots (job_id, original_name, stored_path, created_at) VALUES (?, ?, ?, ?)');

for ($i = 0; $i < count($names); $i++) {
    if ($errors[$i] !== UPLOAD_ERR_OK) {
        $errCount++;
        continue;
    }
    $origName = $names[$i];
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        flash('error', "Dilewati (format tidak didukung): " . h($origName));
        $errCount++;
        continue;
    }

    if ($ext === 'pdf') {
        // PDF berisi screenshot (mis. hasil scan HP atau export Word) — ambil
        // gambar JPEG terbesar di dalamnya, lalu perlakukan seperti screenshot biasa.
        $extractedPath = extract_largest_jpeg_from_pdf($tmpNames[$i]);
        if (!$extractedPath) {
            flash('error', "Tidak ditemukan gambar di dalam PDF: " . h($origName) . " — pastikan PDF berisi screenshot, bukan hanya teks.");
            $errCount++;
            continue;
        }
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($origName, PATHINFO_FILENAME)) . '.jpg';
        $storedPath = $destDir . '/' . uniqid() . '_' . $safeName;
        if (!rename($extractedPath, $storedPath)) {
            @copy($extractedPath, $storedPath);
            @unlink($extractedPath);
        }
        $stmt->execute([$jobId, pathinfo($origName, PATHINFO_FILENAME) . '.jpg', $storedPath, date('Y-m-d H:i:s')]);
        $okCount++;
        $pdfCount++;
        continue;
    }

    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $origName);
    $storedPath = $destDir . '/' . uniqid() . '_' . $safeName;
    if (!move_uploaded_file($tmpNames[$i], $storedPath)) {
        $errCount++;
        continue;
    }

    $stmt->execute([$jobId, $origName, $storedPath, date('Y-m-d H:i:s')]);
    $okCount++;
}

if ($okCount > 0) {
    flash('success', "$okCount screenshot berhasil diunggah." . ($pdfCount > 0 ? " ($pdfCount gambar diekstrak otomatis dari PDF.)" : ''));
}
if ($errCount > 0) {
    flash('error', "$errCount file gagal diunggah.");
}

redirect('../job.php?id=' . $jobId);
