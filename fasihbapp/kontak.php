<?php
declare(strict_types=1);
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';

$title = 'Contact — Fasih BAPP';
require __DIR__ . '/includes/header.php';

$lat = '3.1633181902823675';
$lng = '98.50947536855718';
?>

<h1>Contact</h1>
<p class="subtitle">Informasi kontak dan lokasi kantor.</p>

<div class="card">
    <h2>Alamat</h2>
    <p style="margin:0 0 18px; color:var(--ink-soft)">
        Jl. Letjen Jamin Ginting No.112A, Raya, Kec. Berastagi, Kabupaten Karo, Sumatera Utara 22152
    </p>
    <div class="map-frame">
        <iframe
            src="https://maps.google.com/maps?q=<?= h($lat) ?>,<?= h($lng) ?>&hl=id&z=16&output=embed"
            width="100%" height="320" style="border:0"
            allowfullscreen loading="lazy"
            referrerpolicy="no-referrer-when-downgrade">
        </iframe>
    </div>
    <p style="margin:14px 0 0">
        <a href="https://www.google.com/maps?q=<?= h($lat) ?>,<?= h($lng) ?>" target="_blank" rel="noopener">Buka di Google Maps →</a>
    </p>
</div>

<div class="card">
    <h2>Jam Pelayanan</h2>
    <table>
        <tbody>
        <tr>
            <td style="width:180px; font-weight:600; color:var(--ink)">Senin – Kamis</td>
            <td>07.30 – 16.00 <span class="muted">(istirahat 12.00 – 13.00)</span></td>
        </tr>
        <tr>
            <td style="font-weight:600; color:var(--ink)">Jumat</td>
            <td>07.30 – 16.30 <span class="muted">(istirahat 11.30 – 13.00)</span></td>
        </tr>
        </tbody>
    </table>
</div>

<div class="card">
    <h2>Media Sosial</h2>
    <div class="social-links">
        <a href="https://www.facebook.com/bps.karo/" target="_blank" rel="noopener" class="social-link" title="Facebook">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.18 8.44 9.94v-7.03H7.9v-2.91h2.54V9.85c0-2.51 1.49-3.9 3.77-3.9 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.89h2.78l-.44 2.91h-2.34V22c4.78-.76 8.44-4.92 8.44-9.94z"/></svg>
            <span>Facebook</span>
        </a>
        <a href="https://www.instagram.com/bps_karo/?hl=en" target="_blank" rel="noopener" class="social-link" title="Instagram">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41-.56-.22-.96-.48-1.38-.9-.42-.42-.68-.82-.9-1.38-.16-.42-.36-1.06-.41-2.23-.06-1.27-.07-1.65-.07-4.85s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41 1.27-.06 1.65-.07 4.85-.07zM12 0C8.74 0 8.33.01 7.05.07 5.78.13 4.9.33 4.14.63c-.79.31-1.46.72-2.13 1.38C1.35 2.68.94 3.35.63 4.14.33 4.9.13 5.78.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.06 1.27.26 2.15.56 2.91.31.79.72 1.46 1.38 2.13.67.66 1.34 1.07 2.13 1.38.76.3 1.64.5 2.91.56C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c1.27-.06 2.15-.26 2.91-.56.79-.31 1.46-.72 2.13-1.38.66-.67 1.07-1.34 1.38-2.13.3-.76.5-1.64.56-2.91.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.06-1.27-.26-2.15-.56-2.91-.31-.79-.72-1.46-1.38-2.13C21.32 1.35 20.65.94 19.86.63c-.76-.3-1.64-.5-2.91-.56C15.67.01 15.26 0 12 0zm0 5.84A6.16 6.16 0 1 0 12 18.16 6.16 6.16 0 0 0 12 5.84zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.41-10.85a1.44 1.44 0 1 1-2.88 0 1.44 1.44 0 0 1 2.88 0z"/></svg>
            <span>Instagram</span>
        </a>
        <a href="https://www.youtube.com/@bpskabkaro" target="_blank" rel="noopener" class="social-link" title="YouTube">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M23.5 6.19a3.02 3.02 0 0 0-2.12-2.14C19.51 3.5 12 3.5 12 3.5s-7.51 0-9.38.55A3.02 3.02 0 0 0 .5 6.19 31.6 31.6 0 0 0 0 12a31.6 31.6 0 0 0 .5 5.81 3.02 3.02 0 0 0 2.12 2.14C4.49 20.5 12 20.5 12 20.5s7.51 0 9.38-.55a3.02 3.02 0 0 0 2.12-2.14A31.6 31.6 0 0 0 24 12a31.6 31.6 0 0 0-.5-5.81zM9.6 15.6V8.4l6.4 3.6-6.4 3.6z"/></svg>
            <span>YouTube</span>
        </a>
        <a href="https://karokab.bps.go.id/id" target="_blank" rel="noopener" class="social-link" title="Website">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm6.93 6h-3a15.6 15.6 0 0 0-1.36-4.15A8.03 8.03 0 0 1 18.93 8zM12 4.04c.83 1.2 1.5 2.53 1.94 3.96H10.06c.44-1.43 1.11-2.76 1.94-3.96zM4.26 14a8.1 8.1 0 0 1 0-4h3.38a16.5 16.5 0 0 0 0 4H4.26zm.81 2h3a15.6 15.6 0 0 0 1.36 4.15A8.03 8.03 0 0 1 5.07 16zm3-8h-3a8.03 8.03 0 0 1 4.36-4.15A15.6 15.6 0 0 0 8.07 8zM12 19.96a15.1 15.1 0 0 1-1.94-3.96h3.88c-.44 1.43-1.11 2.76-1.94 3.96zM14.36 14H9.64a14.5 14.5 0 0 1 0-4h4.72a14.5 14.5 0 0 1 0 4zm.02 5.15A15.6 15.6 0 0 0 15.74 16h3a8.03 8.03 0 0 1-4.36 4.15zM16.36 14a16.5 16.5 0 0 0 0-4h3.38a8.1 8.1 0 0 1 0 4h-3.38z"/></svg>
            <span>Website</span>
        </a>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
