<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
$info = $pdo->query("SELECT * FROM informasi_kost LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$wa = preg_replace('/[^0-9]/', '', $info['nomor_kontak'] ?? '081234567890');
if (substr($wa, 0, 1) === '0') $wa = '62' . substr($wa, 1);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak - Kost Mutmainah</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="navbar">
    <div class="logo">Kost Mutmainah</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="kamar.php">Kamar</a>
        <a href="kontak.php" class="active">Kontak</a>
        <?php if (isLoggedIn()): ?>
            <?php if (isPemilik()): ?>
                <a href="pemilik/dashboard.php">Dashboard</a>
            <?php else: ?>
                <a href="penghuni/dashboard.php">Dashboard</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="index.php?login=1" class="btn-login">Login</a>
        <?php endif; ?>
    </nav>
</div>
<div class="kontak-section">
    <h1>Kontak & Bantuan</h1>
    <p class="sub">Hubungi pemilik kost untuk informasi lebih lanjut, pertanyaan, maupun bantuan terkait pemesanan kamar.</p>
    <div class="kontak-box">
        <div class="kontak-left">
            <h3>Hubungi Owner</h3>
            <p>Silakan hubungi owner melalui WhatsApp untuk konsultasi, booking, atau bantuan lainnya.</p>
            <div class="info-item">
                <img src="icon/telepon.png" alt="Telepon" style="width:18px;height:18px;vertical-align:middle;margin-right:8px;">
                <?= htmlspecialchars($info['nomor_kontak'] ?? '-') ?>
            </div>
            <div class="info-item">
                <img src="icon/lokasi.png" alt="Lokasi" style="width:18px;height:18px;vertical-align:middle;margin-right:8px;">
                <?= htmlspecialchars($info['alamat'] ?? '-') ?>
            </div>
        </div>
        <div class="kontak-right">
            <img src="icon/whatsapp.png" alt="WhatsApp" style="width:48px;height:48px;">
            <h3>WhatsApp Owner</h3>
            <p>Klik tombol di bawah untuk langsung terhubung dengan owner kost.</p>
            <a href="https://wa.me/<?= $wa ?>" target="_blank" class="btn btn-success">Chat WhatsApp</a>
        </div>
    </div>
</div>
<footer>© 2026 Kost Mutmainah. All Rights Reserved.</footer>
</body>
</html>