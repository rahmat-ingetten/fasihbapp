<?php
/** @var string $title */
if (!isset($title)) $title = 'Fasih BAPP';
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($title) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css?v=<?= @filemtime(__DIR__ . '/../assets/style.css') ?: time() ?>">
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
        <a href="index.php" class="brand">
            <img src="https://opendata.majalengkakab.go.id/api/static/upload/16385194aeedf729ff5cb5027ebc2c55db758b66602a6bf0a9b8b42c7ce530df.png" alt="Logo" class="brand-logo">
            Fasih BAPP
        </a>
        <nav class="topnav">
            <a href="index.php" class="<?= basename($_SERVER['SCRIPT_NAME']) === 'index.php' ? 'active' : '' ?>">Beranda</a>
            <a href="panduan.php" class="<?= basename($_SERVER['SCRIPT_NAME']) === 'panduan.php' ? 'active' : '' ?>">Cara Guna</a>
            <a href="kontak.php" class="<?= basename($_SERVER['SCRIPT_NAME']) === 'kontak.php' ? 'active' : '' ?>">Contact</a>
            <?php $__authUser = currentUser(); ?>
            <?php if ($__authUser): ?>
                <span class="topnav-user">👤 <?= h($__authUser['nama']) ?></span>
                <form method="post" action="logout.php" class="topnav-logout-form"
                      data-confirm="Yakin ingin logout?">
                    <button type="submit" class="topnav-logout-btn">Logout</button>
                </form>
            <?php else: ?>
                <a href="login.php" class="<?= basename($_SERVER['SCRIPT_NAME']) === 'login.php' ? 'active' : '' ?>">Login</a>
                <a href="register.php" class="<?= basename($_SERVER['SCRIPT_NAME']) === 'register.php' ? 'active' : '' ?>">Daftar</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container">
<?php foreach (getFlashes() as $f): ?>
    <div class="flash flash-<?= h($f['type']) ?>"><?= h($f['message']) ?></div>
<?php endforeach; ?>
