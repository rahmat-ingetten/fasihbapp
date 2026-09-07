<?php
declare(strict_types=1);
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';

$pdo = db();
$jobId = (int) ($_POST['job_id'] ?? 0);
$page = (int) ($_POST['page'] ?? 1);

$stmt = $pdo->prepare('SELECT * FROM jobs WHERE id = ?');
$stmt->execute([$jobId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    flash('error', 'Pekerjaan tidak ditemukan (mungkin sudah terhapus).');
    redirect('../index.php?page=' . max(1, $page));
}

// Hapus baris di database. Tabel docx_files, persons, screenshots, dan process_log
// semuanya punya FK "ON DELETE CASCADE" ke jobs, jadi otomatis ikut terhapus.
$del = $pdo->prepare('DELETE FROM jobs WHERE id = ?');
$del->execute([$jobId]);

// Hapus juga folder fisiknya di storage (upload docx, screenshot, hasil proses, cache).
$folder = jobStoragePath($jobId);
if (is_dir($folder)) {
    deleteDirRecursive($folder);
}

flash('success', 'Pekerjaan "' . $job['nama'] . ' — ' . $job['wilayah'] . '" berhasil dihapus.');
redirect('../index.php?page=' . max(1, $page));

function deleteDirRecursive(string $dir): void
{
    $items = scandir($dir);
    if ($items === false) {
        return;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            deleteDirRecursive($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}
