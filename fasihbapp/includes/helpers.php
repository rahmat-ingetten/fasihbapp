<?php
declare(strict_types=1);

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

function flash(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlashes(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function currentUser(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $stmt = db()->prepare('SELECT id, nama, email FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        unset($_SESSION['user_id']);
        return null;
    }
    $cached = $user;
    return $user;
}

function requireLogin(): array
{
    $user = currentUser();
    if (!$user) {
        flash('error', 'Silakan login terlebih dahulu.');
        redirect('login.php');
    }
    return $user;
}

function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function requireJob(PDO $pdo, int $jobId): array
{
    $stmt = $pdo->prepare('SELECT * FROM jobs WHERE id = ?');
    $stmt->execute([$jobId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$job) {
        http_response_code(404);
        die('Pekerjaan tidak ditemukan.');
    }
    return $job;
}

function formatBytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    $val = $bytes;
    while ($val >= 1024 && $i < count($units) - 1) {
        $val /= 1024;
        $i++;
    }
    return round($val, 1) . ' ' . $units[$i];
}
