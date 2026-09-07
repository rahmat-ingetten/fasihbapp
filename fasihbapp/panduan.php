<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';

$title = 'Cara Guna — Fasih BAPP';
require __DIR__ . '/includes/header.php';
?>

<h1>Cara Guna</h1>
<p class="subtitle">Panduan langkah demi langkah menggunakan Fasih BAPP.</p>

<div class="card">
    <h2>Gambaran Umum</h2>
    <p style="margin:0; color:var(--ink-soft)">
        Aplikasi ini otomatis mencocokkan screenshot dengan nama orang di dalam dokumen Word,
        lalu menyisipkannya ke posisi yang tepat — tanpa perlu buka & edit Word satu per satu secara manual.
        File Word asli tidak pernah diubah; hasilnya selalu disimpan sebagai file baru.
    </p>
</div>

<div class="card">
    <h2>Langkah 1 — Buat Pekerjaan Baru</h2>
    <p style="color:var(--ink-soft)">Dari halaman <a href="index.php">Beranda</a>, isi:</p>
    <ul style="color:var(--ink-soft); line-height:1.9">
        <li><strong>Nama Pekerjaan</strong> — bebas, misalnya "Bukti Pencapaian Fasih"</li>
        <li><strong>Wilayah / Kecamatan</strong> — pilih dari daftar dropdown, atau ketik manual kalau tidak ada di daftar</li>
    </ul>
    <p style="color:var(--ink-soft)">Klik <strong>Buat Pekerjaan</strong> — kamu akan masuk ke halaman kerja pekerjaan tersebut.</p>
</div>

<div class="card">
    <h2>Langkah 2 — Upload Word</h2>
    <p style="color:var(--ink-soft)">
        Upload file <code>.docx</code> yang berisi nama-nama orang beserta bagian
        "Screenshoot Aplikasi Fasih" yang masih kosong. Sistem otomatis membaca:
    </p>
    <ul style="color:var(--ink-soft); line-height:1.9">
        <li>Semua nama orang di dalam dokumen (bisa banyak orang dalam 1 file)</li>
        <li>Lokasi tiap bagian "Screenshoot Aplikasi Fasih" yang perlu diisi gambar</li>
    </ul>
    <p style="color:var(--ink-soft)">
        Kalau muncul peringatan ⚠ "jumlah tidak sama" antara jumlah nama dan jumlah lokasi screenshot,
        periksa kembali format dokumen tersebut sebelum lanjut.
    </p>
</div>

<div class="card">
    <h2>Langkah 3 — Upload Screenshot</h2>
    <p style="color:var(--ink-soft)">
        Upload semua file screenshot sekaligus. Nama file boleh cuma nama depan
        (misalnya <code>Screenshot Aplikasi Fasih Hanna.png</code>) — tidak perlu nama lengkap.
    </p>
    <p style="color:var(--ink-soft)">Format yang didukung:</p>
    <ul style="color:var(--ink-soft); line-height:1.9">
        <li>Gambar langsung: <code>.png</code>, <code>.jpg</code>, <code>.jpeg</code>, <code>.webp</code></li>
        <li>File <code>.pdf</code> yang isinya screenshot (mis. hasil scan HP atau export Word) — gambarnya diekstrak otomatis</li>
    </ul>
</div>

<div class="card">
    <h2>Langkah 4 — Matching</h2>
    <p style="color:var(--ink-soft)">
        Klik <strong>🔄 Cocokkan Otomatis</strong>. Sistem mencocokkan nama ↔ screenshot berdasarkan
        kata kunci di nama file, lalu menampilkan tabel review:
    </p>
    <ul style="color:var(--ink-soft); line-height:1.9">
        <li><span class="badge badge-green">✓ Match</span> — sudah cocok otomatis</li>
        <li><span class="badge badge-green">✓ Manual</span> — kamu ganti manual lewat dropdown</li>
        <li><span class="badge badge-amber">⚠ Kosong</span> — belum ada screenshot yang cocok, pilih manual di dropdown "Ganti manual"</li>
    </ul>
    <p style="color:var(--ink-soft)">Pastikan semua baris sudah ✓ sebelum lanjut ke langkah berikutnya (thumbnail bisa dicek langsung di tabel).</p>
</div>

<div class="card">
    <h2>Langkah 5 — Proses & Download</h2>
    <p style="color:var(--ink-soft)">Klik <strong>▶ Process All</strong>. Sistem otomatis, untuk setiap orang:</p>
    <ul style="color:var(--ink-soft); line-height:1.9">
        <li>Memotong (crop) status bar HP di atas & tombol navigasi di bawah dari screenshot</li>
        <li>Merapatkan crop sampai ke tepi konten (buang ruang kosong)</li>
        <li>Menyisipkan gambar ke posisi "Screenshoot Aplikasi Fasih" yang sesuai</li>
    </ul>
    <p style="color:var(--ink-soft)">
        Setelah selesai, download file satu per satu lewat tombol <strong>Download</strong>,
        atau semuanya sekaligus lewat <strong>⬇ Download Semua (ZIP)</strong>.
    </p>
</div>

<div class="card">
    <h2>Tips & Troubleshooting</h2>
    <ul style="color:var(--ink-soft); line-height:1.9">
        <li>File Word asli <strong>tidak pernah diubah</strong> — hasil selalu jadi file baru bernama <code>..._hasil.docx</code></li>
        <li>Kalau crop otomatis kurang pas untuk sebagian screenshot (mis. ada elemen melayang seperti tombol chat), coba crop manual dulu sebelum upload</li>
        <li>Pekerjaan yang sudah tidak dipakai bisa dihapus dari halaman Beranda lewat tombol 🗑 Hapus (akan diminta konfirmasi dulu)</li>
        <li>Semua data tersimpan lokal di komputer/server ini — tidak diunggah ke internet</li>
    </ul>
</div>

<p style="text-align:center; margin-top:8px">
    <a href="index.php">← Kembali ke Beranda</a>
</p>

<?php require __DIR__ . '/includes/footer.php'; ?>
