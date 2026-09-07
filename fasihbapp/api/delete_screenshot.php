<?php
declare(strict_types=1);
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';

$pdo = db();
$jobId = (int) ($_POST['job_id'] ?? 0);
$job = requireJob($pdo, $jobId);
$screenshotId = (int) ($_POST['screenshot_id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM screenshots WHERE id = ? AND job_id = ?');
$stmt->execute([$screenshotId, $jobId]);
$shot = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shot) {
    flash('error', 'Screenshot tidak ditemukan (mungkin sudah terhapus).');
    redirect('../job.php?id=' . $jobId);
}

// Lepas dulu keterkaitannya dari orang manapun yang sudah cocok pakai screenshot
// ini (tidak ada FK constraint di kolom ini, jadi harus dibersihkan manual)
// supaya tidak ada referensi "menggantung" ke screenshot yang sudah dihapus.
$unlink = $pdo->prepare("UPDATE persons SET screenshot_id = NULL, match_score = 0, match_type = 'none' WHERE screenshot_id = ?");
$unlink->execute([$screenshotId]);

$del = $pdo->prepare('DELETE FROM screenshots WHERE id = ?');
$del->execute([$screenshotId]);

if (is_file($shot['stored_path'])) {
    @unlink($shot['stored_path']);
}

flash('success', 'Screenshot "' . $shot['original_name'] . '" berhasil dihapus.');
redirect('../job.php?id=' . $jobId);
