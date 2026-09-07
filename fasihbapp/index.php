<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';

$pdo = db();

// Kalau diakses langsung ke root (mis. http://localhost/fasih16/ tanpa
// "index.php" eksplisit di URL) dan belum login, lempar ke halaman login dulu.
// Kalau user sengaja buka index.php langsung, tetap boleh lihat-lihat tanpa login.
$__requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$__isBareRoot = substr($__requestPath, -1) === '/';
if (!currentUser() && $__isBareRoot) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_job') {
    requireLogin(); // buat pekerjaan baru wajib login dulu
    $nama = trim($_POST['nama'] ?? '');
    $wilayah = trim($_POST['wilayah'] ?? '');
    if ($nama === '' || $wilayah === '') {
        flash('error', 'Nama pekerjaan dan wilayah wajib diisi.');
        redirect("index.php");
    }
    $stmt = $pdo->prepare('INSERT INTO jobs (nama, wilayah, created_at) VALUES (?, ?, ?)');
    $stmt->execute([$nama, $wilayah, date('Y-m-d H:i:s')]);
    $jobId = (int) $pdo->lastInsertId();
    ensureDir(jobStoragePath($jobId, 'uploads/docx'));
    ensureDir(jobStoragePath($jobId, 'uploads/screenshots'));
    ensureDir(jobStoragePath($jobId, 'output'));
    flash('success', 'Pekerjaan baru berhasil dibuat.');
    redirect('job.php?id=' . $jobId);
}

$jobsPerPage = 2;
$currentPage = max(1, (int) ($_GET['page'] ?? 1));

$totalJobs = (int) $pdo->query('SELECT COUNT(*) FROM jobs')->fetchColumn();
$totalPages = max(1, (int) ceil($totalJobs / $jobsPerPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $jobsPerPage;

$stmt = $pdo->prepare('SELECT j.*,
        (SELECT COUNT(*) FROM docx_files d WHERE d.job_id = j.id) AS docx_count,
        (SELECT COUNT(*) FROM screenshots s WHERE s.job_id = j.id) AS shot_count
    FROM jobs j ORDER BY j.id DESC
    LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $jobsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = 'Fasih BAPP';
require __DIR__ . '/includes/header.php';
?>

<h1>Fasih BAPP</h1>
<p class="subtitle">Otomatis mencocokkan & menyisipkan screenshot ke dalam dokumen Word berdasarkan nama.</p>

<?php $__homeUser = currentUser(); ?>
<?php if ($__homeUser): ?>
    <div class="welcome-banner">👋 Selamat Datang, <strong><?= h($__homeUser['nama']) ?></strong>!</div>
<?php endif; ?>

<div class="card">
    <h2>+ Pekerjaan Baru</h2>
    <form method="post">
        <input type="hidden" name="action" value="create_job">
        <div class="grid-2">
            <div class="field">
                <label for="nama">Nama Pekerjaan</label>
                <input type="text" id="nama" name="nama" placeholder="Bukti Pencapaian Fasih" required>
            </div>
            <div class="field">
                <label for="wilayah">Wilayah / Kecamatan</label>
                <input type="text" id="wilayah" name="wilayah" list="daftar-kecamatan" placeholder="Pilih atau ketik nama kecamatan" autocomplete="off" required>
                <datalist id="daftar-kecamatan">
                    <option value="Barusjahe">
                    <option value="Berastagi">
                    <option value="Dolat Rayat">
                    <option value="Juhar">
                    <option value="Kabanjahe">
                    <option value="Kuta Buluh">
                    <option value="Laubaleng">
                    <option value="Mardingding">
                    <option value="Merdeka">
                    <option value="Merek">
                    <option value="Munte">
                    <option value="Naman Teran">
                    <option value="Payung">
                    <option value="Simpang Empat">
                    <option value="Tiga Binanga">
                    <option value="Tiga Panah">
                    <option value="Tiganderket">
                </datalist>
            </div>
        </div>
        <button type="submit" class="btn">Buat Pekerjaan</button>
    </form>
</div>

<div class="card">
    <h2>Daftar Pekerjaan</h2>
    <?php if (empty($jobs)): ?>
        <div class="empty-state">Belum ada pekerjaan. Buat pekerjaan baru di atas untuk mulai.</div>
    <?php else: ?>
        <div class="job-list">
        <?php foreach ($jobs as $job): ?>
            <div class="job-item">
                <a class="job-item-link" href="job.php?id=<?= (int) $job['id'] ?>">
                    <div>
                        <div class="job-name"><?= h($job['nama']) ?> — <?= h($job['wilayah']) ?></div>
                        <div class="job-meta"><?= (int) $job['docx_count'] ?> file Word · <?= (int) $job['shot_count'] ?> screenshot · dibuat <?= h($job['created_at']) ?></div>
                    </div>
                </a>
                <div class="job-item-actions">
                    <a class="btn btn-secondary btn-sm" href="job.php?id=<?= (int) $job['id'] ?>">Buka →</a>
                    <form method="post" action="api/delete_job.php"
                          data-confirm="Yakin ingin menghapus pekerjaan &quot;<?= h(addslashes($job['nama'])) ?> — <?= h(addslashes($job['wilayah'])) ?>&quot;?

Semua file Word, screenshot, dan hasil proses di dalamnya akan terhapus permanen dan tidak bisa dikembalikan.">
                        <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
                        <input type="hidden" name="page" value="<?= $currentPage ?>">
                        <button type="submit" class="btn btn-danger btn-sm">🗑 Hapus</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <a href="index.php?page=<?= max(1, $currentPage - 1) ?>"
               class="btn btn-secondary btn-sm <?= $currentPage <= 1 ? 'btn-disabled' : '' ?>"
               <?= $currentPage <= 1 ? 'tabindex="-1" aria-disabled="true"' : '' ?>>← Sebelumnya</a>

            <span class="pagination-pages">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="index.php?page=<?= $p ?>" class="pagination-page <?= $p === $currentPage ? 'active' : '' ?>"><?= $p ?></a>
                <?php endfor; ?>
            </span>

            <a href="index.php?page=<?= min($totalPages, $currentPage + 1) ?>"
               class="btn btn-secondary btn-sm <?= $currentPage >= $totalPages ? 'btn-disabled' : '' ?>"
               <?= $currentPage >= $totalPages ? 'tabindex="-1" aria-disabled="true"' : '' ?>>Berikutnya →</a>
        </div>
        <p class="muted" style="text-align:center; margin-top:8px">Halaman <?= $currentPage ?> dari <?= $totalPages ?> · <?= $totalJobs ?> pekerjaan total</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
