<?php
declare(strict_types=1);
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../includes/matcher.php';

$pdo = db();
$jobId = (int) ($_POST['job_id'] ?? 0);
$job = requireJob($pdo, $jobId);

// Ambil semua person yang BELUM dikunci manual
$persons = $pdo->prepare("SELECT id, name FROM persons WHERE job_id = ? AND match_type != 'manual' ORDER BY docx_file_id, order_index");
$persons->execute([$jobId]);
$persons = $persons->fetchAll(PDO::FETCH_KEY_PAIR); // id => name

// Screenshot yang belum dipakai oleh siapapun yang terkunci manual
$usedManual = $pdo->prepare("SELECT screenshot_id FROM persons WHERE job_id = ? AND match_type = 'manual' AND screenshot_id IS NOT NULL");
$usedManual->execute([$jobId]);
$usedManualIds = array_map('intval', $usedManual->fetchAll(PDO::FETCH_COLUMN));

$shotsStmt = $pdo->prepare('SELECT id, original_name FROM screenshots WHERE job_id = ?');
$shotsStmt->execute([$jobId]);
$allShots = $shotsStmt->fetchAll(PDO::FETCH_KEY_PAIR); // id => original_name
$availableShots = array_diff_key($allShots, array_flip($usedManualIds));

if (empty($persons)) {
    flash('error', 'Tidak ada nama untuk dicocokkan. Unggah file Word terlebih dahulu.');
    redirect('../job.php?id=' . $jobId);
}

$result = match_names_to_screenshots($persons, $availableShots);

$pdo->beginTransaction();
// reset dulu semua yang non-manual
$reset = $pdo->prepare("UPDATE persons SET screenshot_id = NULL, match_score = 0, match_type = 'none' WHERE job_id = ? AND match_type != 'manual'");
$reset->execute([$jobId]);

$upd = $pdo->prepare("UPDATE persons SET screenshot_id = ?, match_score = ?, match_type = 'auto' WHERE id = ?");
foreach ($result['matches'] as $personId => $shotId) {
    $upd->execute([$shotId, $result['scores'][$personId], $personId]);
}
$pdo->commit();

$matchedCount = count($result['matches']);
$totalCount = count($persons);
flash('success', "Matching selesai: $matchedCount dari $totalCount nama berhasil dicocokkan otomatis.");
if ($matchedCount < $totalCount) {
    flash('info', ($totalCount - $matchedCount) . ' nama belum ada screenshot yang cocok — cocokkan manual di tabel di bawah.');
}

redirect('../job.php?id=' . $jobId);
