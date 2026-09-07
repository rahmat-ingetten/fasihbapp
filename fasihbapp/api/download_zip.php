<?php
declare(strict_types=1);
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/helpers.php';

$pdo = db();
$jobId = (int) ($_GET['job_id'] ?? 0);
$job = requireJob($pdo, $jobId);

$stmt = $pdo->prepare("
    SELECT d.original_name, pl.output_path
    FROM docx_files d
    JOIN process_log pl ON pl.id = (
        SELECT MAX(pl2.id) FROM process_log pl2
        WHERE pl2.docx_file_id = d.id AND pl2.status = 'success'
    )
    WHERE d.job_id = ?
");
$stmt->execute([$jobId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    http_response_code(404);
    die('Belum ada file hasil untuk diunduh.');
}

$zipName = preg_replace('/[^A-Za-z0-9._-]/', '_', $job['wilayah']) . '_Hasil.zip';
$tmpZip = tempnam(sys_get_temp_dir(), 'zipdl');

$zip = new ZipArchive();
$zip->open($tmpZip, ZipArchive::OVERWRITE);
foreach ($rows as $row) {
    if (!is_file($row['output_path'])) continue;
    $entryName = pathinfo($row['original_name'], PATHINFO_FILENAME) . '_hasil.docx';
    $zip->addFile($row['output_path'], $entryName);
}
$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipName . '"');
header('Content-Length: ' . filesize($tmpZip));
readfile($tmpZip);
unlink($tmpZip);
