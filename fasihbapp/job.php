<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';

$pdo = db();
$jobId = (int) ($_GET['id'] ?? 0);
$job = requireJob($pdo, $jobId);

$docxFiles = $pdo->prepare("
    SELECT d.*,
        (SELECT pl.status FROM process_log pl WHERE pl.docx_file_id = d.id ORDER BY pl.id DESC LIMIT 1) AS last_status,
        (SELECT pl.message FROM process_log pl WHERE pl.docx_file_id = d.id ORDER BY pl.id DESC LIMIT 1) AS last_message
    FROM docx_files d WHERE d.job_id = ? ORDER BY d.id
");
$docxFiles->execute([$jobId]);
$docxFiles = $docxFiles->fetchAll(PDO::FETCH_ASSOC);

$screenshots = $pdo->prepare('SELECT * FROM screenshots WHERE job_id = ? ORDER BY id');
$screenshots->execute([$jobId]);
$screenshots = $screenshots->fetchAll(PDO::FETCH_ASSOC);

$persons = $pdo->prepare('
    SELECT p.*, s.original_name AS shot_name, d.original_name AS docx_name
    FROM persons p
    LEFT JOIN screenshots s ON s.id = p.screenshot_id
    JOIN docx_files d ON d.id = p.docx_file_id
    WHERE p.job_id = ?
    ORDER BY p.docx_file_id, p.order_index
');
$persons->execute([$jobId]);
$persons = $persons->fetchAll(PDO::FETCH_ASSOC);

$totalPersons = count($persons);
$matchedPersons = count(array_filter($persons, fn($p) => $p['screenshot_id'] !== null));
$hasDocx = count($docxFiles) > 0;
$hasScreenshots = count($screenshots) > 0;
$hasProcessed = count(array_filter($docxFiles, fn($d) => $d['last_status'] === 'success')) > 0;

$title = $job['nama'] . ' — ' . $job['wilayah'];
require __DIR__ . '/includes/header.php';
?>

<h1><?= h($job['nama']) ?></h1>
<p class="subtitle">Wilayah: <strong><?= h($job['wilayah']) ?></strong> · <a href="index.php">← Kembali ke daftar pekerjaan</a></p>

<div class="steps">
    <div class="step <?= $hasDocx ? 'done' : 'active' ?>">1. Upload Word</div>
    <div class="step <?= $hasScreenshots ? 'done' : ($hasDocx ? 'active' : '') ?>">2. Upload Screenshot</div>
    <div class="step <?= $matchedPersons > 0 ? 'done' : ($hasScreenshots ? 'active' : '') ?>">3. Matching</div>
    <div class="step <?= $hasProcessed ? 'done' : ($matchedPersons > 0 ? 'active' : '') ?>">4. Proses & Download</div>
</div>

<!-- STEP 1: Upload Word -->
<div class="card">
    <h2>1. Upload Word (belum ada screenshot)</h2>
    <form method="post" action="api/upload_docx.php" enctype="multipart/form-data">
        <input type="hidden" name="job_id" value="<?= $jobId ?>">
        <div class="field">
            <input type="file" name="docx_files[]" accept=".docx" multiple required>
        </div>
        <button type="submit" class="btn">Upload Word</button>
    </form>

    <?php if ($hasDocx): ?>
    <table style="margin-top:16px">
        <thead><tr><th>Nama File</th><th>Jumlah Nama</th><th>Jumlah Lokasi Screenshot</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($docxFiles as $d): ?>
            <tr>
                <td><?= h($d['original_name']) ?></td>
                <td><?= (int) $d['person_count'] ?></td>
                <td><?= (int) $d['marker_count'] ?></td>
                <td>
                    <?php if ($d['person_count'] !== $d['marker_count']): ?>
                        <span class="badge badge-amber">⚠ Jumlah tidak sama</span>
                    <?php elseif ($d['last_status'] === 'success'): ?>
                        <span class="badge badge-green">✓ Sudah diproses</span>
                    <?php else: ?>
                        <span class="badge badge-gray">Siap</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="post" action="api/delete_docx.php"
                          data-confirm="Hapus file &quot;<?= h(addslashes($d['original_name'])) ?>&quot;? Semua nama & hasil proses dari file ini akan ikut terhapus.">
                        <input type="hidden" name="job_id" value="<?= $jobId ?>">
                        <input type="hidden" name="docx_id" value="<?= (int) $d['id'] ?>">
                        <button type="submit" class="btn-icon-remove" title="Hapus file ini">✕</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- STEP 2: Upload Screenshot -->
<div class="card">
    <h2>2. Upload Screenshot</h2>
    <form method="post" action="api/upload_screenshots.php" enctype="multipart/form-data">
        <input type="hidden" name="job_id" value="<?= $jobId ?>">
        <div class="field">
            <input type="file" name="screenshots[]" accept=".png,.jpg,.jpeg,.webp,.pdf" multiple required>
            <p class="muted" style="margin-top:6px">Bisa juga upload file PDF yang isinya screenshot (mis. hasil scan HP) — gambarnya akan diambil otomatis.</p>
        </div>
        <button type="submit" class="btn">Upload Screenshot</button>
    </form>

    <?php if ($hasScreenshots): ?>
        <p class="muted" style="margin-top:14px"><?= count($screenshots) ?> screenshot terunggah.</p>
        <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:8px">
            <?php foreach ($screenshots as $s): ?>
                <div style="text-align:center; width:70px; position:relative">
                    <form method="post" action="api/delete_screenshot.php"
                          data-confirm="Hapus screenshot &quot;<?= h(addslashes($s['original_name'])) ?>&quot;?"
                          style="position:absolute; top:-6px; right:-6px; margin:0">
                        <input type="hidden" name="job_id" value="<?= $jobId ?>">
                        <input type="hidden" name="screenshot_id" value="<?= (int) $s['id'] ?>">
                        <button type="submit" class="btn-icon-remove btn-icon-remove-sm" title="Hapus screenshot ini">✕</button>
                    </form>
                    <img class="thumb" style="width:70px;height:70px" src="thumb.php?id=<?= (int)$s['id'] ?>&w=140" alt="">
                    <div class="muted" style="font-size:11px; word-break:break-all"><?= h(mb_strimwidth($s['original_name'], 0, 16, '…')) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- STEP 3: Matching -->
<?php if ($hasDocx && $hasScreenshots): ?>
<div class="card">
    <h2>3. Matching</h2>
    <div class="toolbar">
        <div class="muted"><?= $matchedPersons ?> / <?= $totalPersons ?> nama sudah punya screenshot</div>
        <form method="post" action="api/run_matching.php">
            <input type="hidden" name="job_id" value="<?= $jobId ?>">
            <button type="submit" class="btn btn-accent">🔄 Cocokkan Otomatis</button>
        </form>
    </div>

    <table>
        <thead><tr><th style="width:36px">No</th><th>Nama</th><th>Screenshot</th><th style="width:110px">Status</th><th>Ganti manual</th></tr></thead>
        <tbody>
        <?php foreach ($persons as $i => $p): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= h($p['name']) ?><?php if (count($docxFiles) > 1): ?><br><span class="muted"><?= h($p['docx_name']) ?></span><?php endif; ?></td>
                <td>
                    <?php if ($p['screenshot_id']): ?>
                        <div style="display:flex;align-items:center;gap:8px">
                            <img class="thumb" src="thumb.php?id=<?= (int)$p['screenshot_id'] ?>" alt="">
                            <span><?= h($p['shot_name']) ?></span>
                        </div>
                    <?php else: ?>
                        <span class="muted">— belum ada —</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($p['match_type'] === 'manual'): ?>
                        <span class="badge badge-green">✓ Manual</span>
                    <?php elseif ($p['match_type'] === 'auto'): ?>
                        <span class="badge badge-green">✓ Match</span>
                    <?php else: ?>
                        <span class="badge badge-amber">⚠ Kosong</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="post" action="api/set_match.php" class="manual-match-form">
                        <input type="hidden" name="job_id" value="<?= $jobId ?>">
                        <input type="hidden" name="person_id" value="<?= (int) $p['id'] ?>">
                        <input type="hidden" name="screenshot_id" class="manual-match-hidden" value="<?= $p['screenshot_id'] ? (int) $p['screenshot_id'] : '' ?>">
                        <input type="text" class="manual-match-input select-inline" list="shots-datalist"
                               value="<?= h($p['shot_name'] ?? '') ?>"
                               placeholder="— tidak ada — (ketik utk cari)" autocomplete="off">
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <datalist id="shots-datalist">
        <?php foreach ($screenshots as $s): ?>
            <option value="<?= h($s['original_name']) ?>">
        <?php endforeach; ?>
    </datalist>

    <script>
    (function () {
        var SHOTS_MAP = <?= json_encode(array_column($screenshots, 'id', 'original_name'), JSON_UNESCAPED_SLASHES) ?>;
        document.querySelectorAll('.manual-match-form').forEach(function (form) {
            var input = form.querySelector('.manual-match-input');
            var hidden = form.querySelector('.manual-match-hidden');
            input.addEventListener('change', function () {
                var typed = input.value.trim();
                if (typed === '') {
                    hidden.value = '';
                    form.submit();
                    return;
                }
                if (Object.prototype.hasOwnProperty.call(SHOTS_MAP, typed)) {
                    hidden.value = SHOTS_MAP[typed];
                    form.submit();
                } else {
                    alert('Screenshot "' + typed + '" tidak ditemukan. Ketik sebagian nama, lalu pilih dari saran yang muncul.');
                    input.value = input.defaultValue;
                }
            });
        });
    })();
    </script>
</div>
<?php endif; ?>

<!-- STEP 4: Process & Download -->
<?php if ($totalPersons > 0): ?>
<div class="card">
    <h2>4. Proses & Download</h2>
    <form method="post" action="api/process.php" onsubmit="document.getElementById('processBtn').innerHTML='<span class=spinner></span> Memproses...'; document.getElementById('processBtn').disabled=true;">
        <input type="hidden" name="job_id" value="<?= $jobId ?>">
        <button type="submit" id="processBtn" class="btn">▶ Process All (<?= $matchedPersons ?>/<?= $totalPersons ?> siap)</button>
    </form>

    <?php if ($hasProcessed): ?>
        <table style="margin-top:16px">
            <thead><tr><th>Nama File</th><th>Hasil</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($docxFiles as $d): ?>
                <tr>
                    <td><?= h($d['original_name']) ?></td>
                    <td><?= h($d['last_message'] ?? '-') ?></td>
                    <td>
                        <?php if ($d['last_status'] === 'success'): ?>
                            <span class="badge badge-green">✓ Selesai</span>
                        <?php elseif ($d['last_status'] === 'error'): ?>
                            <span class="badge badge-red">✗ Gagal</span>
                        <?php else: ?>
                            <span class="badge badge-gray">Belum diproses</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($d['last_status'] === 'success'): ?>
                            <a class="btn btn-secondary btn-sm" href="api/download.php?docx_id=<?= (int) $d['id'] ?>">Download</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div style="margin-top:14px">
            <a class="btn btn-accent" href="api/download_zip.php?job_id=<?= $jobId ?>">⬇ Download Semua (ZIP)</a>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
