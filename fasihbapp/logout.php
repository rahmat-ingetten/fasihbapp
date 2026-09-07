<?php
declare(strict_types=1);
require __DIR__ . '/includes/helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$_SESSION = [];
session_destroy();

flash('success', 'Kamu berhasil logout.');
redirect('login.php');
