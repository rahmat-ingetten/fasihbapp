<?php
declare(strict_types=1);
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';

$pdo = db();
$jobId = (int) ($_POST['job_id'] ?? 0);
$job = requireJob($pdo, $jobId);
$personId = (int) ($_POST['person_id'] ?? 0);
$screenshotId = $_POST['screenshot_id'] ?? '';

$person = $pdo->prepare('SELECT * FROM persons WHERE id = ? AND job_id = ?');
$person->execute([$personId, $jobId]);
$person = $person->fetch(PDO::FETCH_ASSOC);
if (!$person) {
    flash('error', 'Data orang tidak ditemukan.');
    redirect('../job.php?id=' . $jobId);
}

if ($screenshotId === '') {
    $stmt = $pdo->prepare("UPDATE persons SET screenshot_id = NULL, match_score = 0, match_type = 'none' WHERE id = ?");
    $stmt->execute([$personId]);
    flash('success', 'Pencocokan untuk ' . $person['name'] . ' dibatalkan.');
} else {
    $screenshotId = (int) $screenshotId;
    $shot = $pdo->prepare('SELECT * FROM screenshots WHERE id = ? AND job_id = ?');
    $shot->execute([$screenshotId, $jobId]);
    $shot = $shot->fetch(PDO::FETCH_ASSOC);
    if (!$shot) {
        flash('error', 'Screenshot tidak ditemukan.');
        redirect('../job.php?id=' . $jobId);
    }
    $stmt = $pdo->prepare("UPDATE persons SET screenshot_id = ?, match_score = 99, match_type = 'manual' WHERE id = ?");
    $stmt->execute([$screenshotId, $personId]);
    flash('success', $person['name'] . ' dicocokkan manual ke ' . $shot['original_name'] . '.');
}

redirect('../job.php?id=' . $jobId);
