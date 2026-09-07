<?php
declare(strict_types=1);
require __DIR__ . '/../includes/db.php';

$pdo = db();
$docxId = (int) ($_GET['docx_id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT d.original_name, pl.output_path
    FROM docx_files d
    JOIN process_log pl ON pl.docx_file_id = d.id AND pl.status = 'success'
    WHERE d.id = ?
    ORDER BY pl.id DESC LIMIT 1
");
$stmt->execute([$docxId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || !is_file($row['output_path'])) {
    http_response_code(404);
    die('File hasil tidak ditemukan. Jalankan proses terlebih dahulu.');
}

$downloadName = pathinfo($row['original_name'], PATHINFO_FILENAME) . '_hasil.docx';

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($row['output_path']));
readfile($row['output_path']);
