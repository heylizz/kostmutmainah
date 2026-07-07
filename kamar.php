<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
$kamar = $pdo->query("SELECT * FROM kamar LIMIT 1")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi Kamar - Kost Mutmainah</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="navbar">
    <div class="logo">Kost Mutmainah</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="kamar.php" class="active">Kamar</a>
        <a href="kontak.php">Kontak</a>
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
<div class="kamar-section">
    <h1>Informasi Kamar</h1>
    <p class="sub">Kamar nyaman dengan fasilitas lengkap dan lingkungan bersih untuk mendukung kebutuhan penghuni.</p>
    <?php if ($kamar): ?>
    <div class="kamar-card">
        <div class="kamar-img">
            <?php if (!empty($kamar['foto'])): ?>
                <img src="uploads/<?= htmlspecialchars($kamar['foto']) ?>" alt="Foto Kamar">
            <?php else: ?>
                Foto Kamar
            <?php endif; ?>
        </div>
        <div class="kamar-info">
            <span class="<?= $kamar['status'] === 'Kosong' ? 'badge-tersedia' : 'badge-penuh' ?>">
                <?= $kamar['status'] === 'Kosong' ? 'Tersedia' : 'Penuh' ?>
            </span>
            <h2>Kamar Kost Mutmainah</h2>
            <div class="harga">Rp<?= number_format($kamar['harga_sewa'], 0, ',', '.') ?> / bulan</div>
            <ul class="fasilitas-list">
                <?php
                $fas = explode(',', $kamar['fasilitas']);
$icons = [
    '<img src="icon/kasur.png" style="width:20px;height:20px;object-fit:contain;vertical-align:middle;">',
    '<img src="icon/lemari.png" style="width:20px;height:20px;object-fit:contain;vertical-align:middle;">',
    '<img src="icon/wifi.png" style="width:20px;height:20px;object-fit:contain;vertical-align:middle;">',
    '<img src="icon/kamar mandi.png" style="width:20px;height:20px;object-fit:contain;vertical-align:middle;">',
    '<img src="icon/parkir.png" style="width:20px;height:20px;object-fit:contain;vertical-align:middle;">',
];
foreach ($fas as $i => $f): ?>
<li>
    <span class="icon"><?= $icons[$i % count($icons)] ?></span>
    <?= trim(htmlspecialchars($f)) ?>
</li>
                <?php endforeach; ?>
            </ul>
           <a href="booking.php?id_kamar=<?= urlencode($kamar['id_kamar']) ?>" class="btn btn-primary">Pesan Sekarang</a>
        </div>
    </div>
    <?php endif; ?>
</div>
<footer>© 2026 Kost Mutmainah. All Rights Reserved.</footer>
</body>
</html>