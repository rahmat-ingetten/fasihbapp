<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/image_lib.php';

$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$w = max(20, min(400, (int) ($_GET['w'] ?? 60)));

$stmt = $pdo->prepare('SELECT stored_path FROM screenshots WHERE id = ?');
$stmt->execute([$id]);
$path = $stmt->fetchColumn();

if (!$path || !is_file($path)) {
    http_response_code(404);
    exit;
}

try {
    $src = load_image_any_format($path);
} catch (Throwable $e) {
    http_response_code(415);
    header('Content-Type: text/plain');
    echo 'Format tidak didukung';
    exit;
}
$origW = imagesx($src);
$origH = imagesy($src);

$h = (int) round($origH * ($w / $origW));
$thumb = imagecreatetruecolor($w, $h);
imagecopyresampled($thumb, $src, 0, 0, 0, 0, $w, $h, $origW, $origH);

header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');
imagepng($thumb);
imagedestroy($thumb);
imagedestroy($src);
