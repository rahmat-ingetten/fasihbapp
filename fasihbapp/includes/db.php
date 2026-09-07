<?php
declare(strict_types=1);

/**
 * Koneksi database MySQL/MariaDB — data akan muncul di phpMyAdmin.
 *
 * Ganti nilai di bawah ini sesuai setting MySQL kamu (XAMPP default biasanya
 * host=localhost, user=root, password kosong).
 */
const DB_HOST = 'localhost';
const DB_PORT = '3306';
const DB_NAME = 'fasih_bapp';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    // Sambung dulu tanpa nama database, buat database-nya kalau belum ada,
    // baru sambung lagi KE database itu. Jadi user tidak perlu bikin database
    // manual dulu di phpMyAdmin — otomatis dibuatkan saat aplikasi pertama jalan.
    try {
        $bootstrap = new PDO(
            'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $bootstrap->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    } catch (PDOException $e) {
        die(
            'Gagal konek ke MySQL. Pastikan MySQL sudah jalan (cek XAMPP Control Panel) ' .
            'dan setting di includes/db.php (DB_HOST, DB_USER, DB_PASS) sudah benar.<br>' .
            'Detail error: ' . htmlspecialchars($e->getMessage())
        );
    }

    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    ensureSchema($pdo);

    return $pdo;
}

function ensureSchema(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    // Cek salah satu tabel inti sudah ada — kalau belum, buat semua tabel.
    $exists = $pdo->query("SHOW TABLES LIKE 'jobs'")->fetch();
    if (!$exists) {
        $pdo->exec(<<<SQL
        CREATE TABLE jobs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nama VARCHAR(255) NOT NULL,
            wilayah VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL);

        $pdo->exec(<<<SQL
        CREATE TABLE docx_files (
            id INT PRIMARY KEY AUTO_INCREMENT,
            job_id INT NOT NULL,
            original_name VARCHAR(500) NOT NULL,
            stored_path TEXT NOT NULL,
            person_count INT NOT NULL DEFAULT 0,
            marker_count INT NOT NULL DEFAULT 0,
            status VARCHAR(50) NOT NULL DEFAULT 'uploaded',
            created_at DATETIME NOT NULL,
            FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL);

        $pdo->exec(<<<SQL
        CREATE TABLE persons (
            id INT PRIMARY KEY AUTO_INCREMENT,
            job_id INT NOT NULL,
            docx_file_id INT NOT NULL,
            order_index INT NOT NULL,
            name VARCHAR(500) NOT NULL,
            screenshot_id INT NULL,
            match_score INT NOT NULL DEFAULT 0,
            match_type VARCHAR(20) NOT NULL DEFAULT 'none',
            FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
            FOREIGN KEY (docx_file_id) REFERENCES docx_files(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL);

        $pdo->exec(<<<SQL
        CREATE TABLE screenshots (
            id INT PRIMARY KEY AUTO_INCREMENT,
            job_id INT NOT NULL,
            original_name VARCHAR(500) NOT NULL,
            stored_path TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL);

        $pdo->exec(<<<SQL
        CREATE TABLE process_log (
            id INT PRIMARY KEY AUTO_INCREMENT,
            docx_file_id INT NOT NULL,
            status VARCHAR(50) NOT NULL,
            message TEXT,
            output_path TEXT,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (docx_file_id) REFERENCES docx_files(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL);

        $pdo->exec(<<<SQL
        CREATE TABLE users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nama VARCHAR(191) NOT NULL UNIQUE,
            email VARCHAR(191) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL);
    } else {
        // Instalasi lama (sebelum fitur login ditambahkan) mungkin belum punya
        // tabel users — buat kalau belum ada, tanpa ganggu data yang sudah ada.
        $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nama VARCHAR(191) NOT NULL UNIQUE,
            email VARCHAR(191) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL);
    }
}

function jobStoragePath(int $jobId, string ...$parts): string
{
    $base = __DIR__ . '/../storage/jobs/' . $jobId;
    foreach ($parts as $p) {
        $base .= '/' . $p;
    }
    return $base;
}

function ensureDir(string $path): void
{
    if (!is_dir($path)) {
        mkdir($path, 0775, true);
    }
}
