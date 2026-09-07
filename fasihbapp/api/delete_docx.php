<?php
declare(strict_types=1);
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';

$pdo = db();
$jobId = (int) ($_POST['job_id'] ?? 0);
$job = requireJob($pdo, $jobId);
$docxId = (int) ($_POST['docx_id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM docx_files WHERE id = ? AND job_id = ?');
$stmt->execute([$docxId, $jobId]);
$docx = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$docx) {
    flash('error', 'File Word tidak ditemukan (mungkin sudah terhapus).');
    redirect('../job.php?id=' . $jobId);
}

// Ambil dulu path file hasil proses (kalau ada) sebelum baris DB-nya terhapus,
// supaya file fisiknya juga ikut dibersihkan.
$outputs = $pdo->prepare('SELECT output_path FROM process_log WHERE docx_file_id = ? AND output_path IS NOT NULL');
$outputs->execute([$docxId]);
$outputPaths = $outputs->fetchAll(PDO::FETCH_COLUMN);

// Hapus baris docx_files — otomatis ikut menghapus persons & process_log terkait
// (FK "ON DELETE CASCADE").
$del = $pdo->prepare('DELETE FROM docx_files WHERE id = ?');
$del->execute([$docxId]);

// Bersihkan file fisik: file asli yang diupload & semua file hasil proses.
if (is_file($docx['stored_path'])) {
    @unlink($docx['stored_path']);
}
foreach ($outputPaths as $path) {
    if ($path && is_file($path)) {
        @unlink($path);
    }
}

flash('success', 'File "' . $docx['original_name'] . '" berhasil dihapus. Silakan upload ulang file yang benar.');
redirect('../job.php?id=' . $jobId);
