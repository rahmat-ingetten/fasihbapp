# Fasih BAPP

Aplikasi lokal untuk otomatis mencocokkan & menyisipkan screenshot bukti pencapaian ke dalam dokumen Word (Berita Acara Pencapaian Pekerjaan / BAPP) berdasarkan nama.

Dibuat dengan **PHP native** — tidak perlu Composer atau library luar apapun.

## 📘 Manual Book

Dokumentasi lengkap cara instalasi dan penggunaan aplikasi ini tersedia dalam bentuk Manual Book (PDF):

📄 [**Manual Book Fasih BAPP.pdf**](manual%20book%20bapp.pdf)

Manual Book ini mencakup:
- Latar belakang, tujuan pengembangan, dan spesifikasi sistem
- Panduan instalasi XAMPP dan cara menghidupkan aplikasi
- Panduan lengkap penggunaan aplikasi, dari mendaftar akun hingga download hasil

## Struktur File

```
fasihbapp/
├── index.php               ← Halaman Beranda (daftar & buat pekerjaan)
├── job.php                 ← Halaman kerja utama (upload, matching, proses)
├── login.php               ← Halaman login
├── register.php            ← Halaman pendaftaran akun
├── logout.php              ← Proses keluar akun
├── panduan.php             ← Halaman Cara Guna (panduan penggunaan)
├── kontak.php              ← Halaman kontak
├── thumb.php               ← Generator thumbnail screenshot
├── README.md               ← Dokumentasi proyek
│
├── docs/
│   └── Manual_Book_Fasih_BAPP.pdf  ← Manual book / panduan penggunaan lengkap
│
├── api/
│   ├── upload_docx.php         ← Endpoint upload dokumen Word
│   ├── upload_screenshots.php  ← Endpoint upload screenshot
│   ├── run_matching.php        ← Endpoint pencocokan otomatis
│   ├── set_match.php           ← Endpoint ubah hasil match manual
│   ├── process.php             ← Endpoint proses (crop + sisip gambar)
│   ├── download.php            ← Endpoint download 1 file hasil
│   ├── download_zip.php        ← Endpoint download semua hasil (ZIP)
│   ├── delete_job.php          ← Endpoint hapus pekerjaan
│   ├── delete_docx.php         ← Endpoint hapus file Word yang diunggah
│   └── delete_screenshot.php   ← Endpoint hapus screenshot yang diunggah
│
├── includes/
│   ├── db.php               ← Koneksi & konfigurasi database MySQL
│   ├── docx_lib.php         ← Library baca/tulis/sisip gambar ke dokumen Word
│   ├── image_lib.php        ← Library crop & resize screenshot
│   ├── matcher.php          ← Logika pencocokan nama ↔ screenshot
│   ├── helpers.php          ← Fungsi bantu umum
│   ├── header.php           ← Template header HTML
│   └── footer.php           ← Template footer HTML
│
├── assets/
│   └── style.css            ← Stylesheet utama aplikasi
│
└── storage/                 ← Data file tersimpan
    └── jobs/{id}/
        ├── uploads/docx/          ← Dokumen Word asli yang diunggah
        ├── uploads/screenshots/   ← Screenshot asli yang diunggah
        ├── cache/cropped/         ← Hasil crop screenshot (cache)
        └── output/                ← Dokumen hasil akhir (..._hasil.docx)
```

## Fitur Utama

### 🔐 Autentikasi Pengguna
- Pendaftaran akun baru (Nama, Email, Kata Sandi)
- Login & logout

### 📁 Manajemen Pekerjaan
- Buat pekerjaan baru (nama pekerjaan & wilayah/kecamatan)
- Lihat, buka kembali, dan hapus pekerjaan dari halaman Beranda

### 📄 Upload Dokumen Word (BAPP)
- Upload file `.docx` berisi banyak nama orang sekaligus
- Sistem otomatis membaca semua nama orang di dalam dokumen
- Sistem otomatis mendeteksi lokasi bagian "Screenshoot Aplikasi Fasih" yang masih kosong

### 📸 Upload Screenshot
- Upload banyak file screenshot HP sekaligus
- Nama file boleh cuma nama depan (misal `Screenshot Aplikasi Fasih Hanna.png`)

### 🔄 Pencocokan Otomatis (Matching)
- Mencocokkan nama ↔ screenshot berdasarkan kata kunci pada nama file
- Status per baris: **Match** (otomatis), **Manual** (diubah pengguna), atau **Kosong**
- Bisa diubah manual lewat dropdown jika hasil pencocokan otomatis kurang tepat

### ⚙️ Proses & Auto-Crop
- Crop otomatis status bar atas & tombol navigasi bawah pada screenshot
- Resize proporsional & sisip gambar ke posisi yang tepat di dokumen Word
- Dokumen Word **asli tidak pernah diubah** — hasil selalu disimpan sebagai file baru (`..._hasil.docx`)

### ⬇️ Download Hasil
- Download dokumen hasil satu per satu
- Atau download semuanya sekaligus dalam bentuk ZIP

## Cara Menjalankan

1. Salin folder `fasihbapp` ke folder web server:
   - **XAMPP**: `C:\xampp\htdocs\fasihbapp`
   - **Laragon**: `C:\laragon\www\fasihbapp`
2. Nyalakan **Apache** dan **MySQL** di XAMPP/Laragon Control Panel (dua-duanya harus jalan).
3. Buka browser ke:
   ```
   http://localhost/fasihbapp/
   ```

Database & tabelnya **otomatis dibuat sendiri** saat aplikasi pertama kali diakses — tidak perlu membuat database secara manual di phpMyAdmin.

## Persyaratan Sistem

| Komponen         | Versi Minimum / Keterangan                                  |
|-------------------|--------------------------------------------------------------|
| PHP               | 8.0+                                                          |
| Web Server        | Apache (bagian dari XAMPP/Laragon)                           |
| Database          | MySQL (bagian dari XAMPP/Laragon)                            |
| Extension PHP     | `zip`, `gd`, `pdo_mysql`, `dom`/`xml`, `mbstring`             |
| Browser           | Chrome / Firefox / Edge versi terbaru                        |
| Koneksi Internet  | Tidak diperlukan — aplikasi berjalan sepenuhnya secara lokal  |

## Database

Nama database: **`fasih_bapp`** (dibuat otomatis saat aplikasi pertama kali dibuka)

| Tabel          | Isi                                                       |
|----------------|------------------------------------------------------------|
| `users`        | Data akun pengguna                                         |
| `jobs`         | Data pekerjaan (nama pekerjaan, wilayah/kecamatan)          |
| `docx_files`   | Data dokumen Word yang diunggah per pekerjaan               |
| `persons`      | Nama-nama orang yang terbaca dari dokumen Word              |
| `screenshots`  | Data screenshot yang diunggah & hasil pencocokannya         |
| `process_log`  | Riwayat proses yang telah dijalankan                        |

Semua data bisa dilihat/diedit langsung lewat **phpMyAdmin** (`http://localhost/phpmyadmin`).

## Konfigurasi

Jika pengaturan MySQL berbeda dari bawaan XAMPP/Laragon (misalnya memakai kata sandi), sesuaikan di bagian atas file `includes/db.php`:

```php
const DB_HOST = 'localhost';
const DB_PORT = '3306';
const DB_NAME = 'fasih_bapp';
const DB_USER = 'root';
const DB_PASS = '';
```

Pastikan juga folder `storage/` bisa ditulis (writable) oleh web server. Di Windows biasanya sudah otomatis bisa — kalau muncul error "gagal menulis", klik kanan folder `storage` → Properties → pastikan tidak read-only.

## Catatan Teknis

- **Pencocokan nama di Word** — sistem mencari paragraf berpola `<no>. Nama : <NAMA>` yang diikuti baris berisi "NIK" (bukan "NIP", yang dipakai untuk nama supervisor tetap).
- **Lokasi sisip screenshot** — ditandai teks "Screenshoot Aplikasi Fasih" (atau varian ejaan "Screenshot") di dalam dokumen, satu marker per orang, urut sesuai urutan nama.
- **Auto-crop screenshot** — mendeteksi status bar/nav bar dari keseragaman warna baris piksel di tepi atas & bawah gambar (maksimal 10% tinggi gambar per sisi), supaya aman jika screenshot ternyata sudah bersih/tidak berbentuk khas HP.
- Jika hasil crop otomatis kurang pas untuk sebagian screenshot (misalnya ada elemen melayang seperti tombol chat), cara termudah adalah meng-crop manual dulu sebelum upload — sistem tetap akan resize & sisip dengan benar.

## Troubleshooting

| Masalah | Penyebab / Solusi |
|---|---|
| "Gagal konek ke MySQL" | Pastikan MySQL di XAMPP/Laragon Control Panel sudah **Start**, bukan cuma Apache. Cocokkan `DB_HOST`/`DB_USER`/`DB_PASS` di `includes/db.php`. |
| Peringatan "jumlah tidak sama" | Jumlah nama dan jumlah marker "Screenshoot Aplikasi Fasih" di dokumen Word tidak sama — periksa kembali format dokumen. |
| Error "gagal menulis" saat upload/proses | Folder `storage/` tidak writable — pastikan tidak dalam mode read-only. |

## Teknologi

- **Backend**: PHP 8+ Native (tanpa framework)
- **Database**: MySQL + PDO
- **Frontend**: HTML, CSS (vanilla), JavaScript
