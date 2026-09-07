<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';

$pdo = db();

if (currentUser()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sandi = $_POST['sandi'] ?? '';
    $sandiUlang = $_POST['sandi_ulang'] ?? '';

    $errors = [];
    if ($nama === '') {
        $errors[] = 'Nama wajib diisi.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid.';
    }
    if (strlen($sandi) < 6) {
        $errors[] = 'Sandi minimal 6 karakter.';
    }
    if ($sandi !== $sandiUlang) {
        $errors[] = 'Konfirmasi sandi tidak sama.';
    }

    if (empty($errors)) {
        $check = $pdo->prepare('SELECT id FROM users WHERE nama = ? OR email = ?');
        $check->execute([$nama, $email]);
        if ($check->fetch()) {
            $errors[] = 'Nama atau email sudah terdaftar. Coba nama lain atau langsung login.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO users (nama, email, password_hash, created_at) VALUES (?, ?, ?, ?)');
        $stmt->execute([$nama, $email, password_hash($sandi, PASSWORD_DEFAULT), date('Y-m-d H:i:s')]);
        flash('success', 'Registrasi berhasil! Silakan login.');
        redirect('login.php');
    }

    foreach ($errors as $e) {
        flash('error', $e);
    }
}

$title = 'Daftar — Fasih BAPP';
require __DIR__ . '/includes/header.php';
?>

<div class="auth-wrap">
    <div class="card auth-card">
        <h1 style="font-size:22px">Daftar Akun</h1>
        <p class="subtitle" style="margin-bottom:24px">Buat akun baru untuk mulai menggunakan aplikasi.</p>

        <form method="post">
            <div class="field">
                <label for="nama">Nama</label>
                <input type="text" id="nama" name="nama" value="<?= h($_POST['nama'] ?? '') ?>" required autofocus>
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input type="text" id="email" name="email" value="<?= h($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="field">
                <label for="sandi">Sandi</label>
                <div class="password-field">
                    <input type="password" id="sandi" name="sandi" minlength="6" required>
                    <button type="button" class="password-toggle" data-target="sandi" aria-label="Tampilkan sandi">
                        <svg class="icon-eye" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="icon-eye-off" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a20.5 20.5 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 7 11 7a20.5 20.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
            </div>
            <div class="field">
                <label for="sandi_ulang">Ulangi Sandi</label>
                <div class="password-field">
                    <input type="password" id="sandi_ulang" name="sandi_ulang" minlength="6" required>
                    <button type="button" class="password-toggle" data-target="sandi_ulang" aria-label="Tampilkan sandi">
                        <svg class="icon-eye" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="icon-eye-off" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a20.5 20.5 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 7 11 7a20.5 20.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn" style="width:100%; justify-content:center">Daftar</button>
        </form>

        <p class="muted" style="text-align:center; margin-top:18px">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </p>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
