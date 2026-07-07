<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requirePemilik();

$kamar_kosong   = $pdo->query("SELECT COUNT(*) FROM kamar WHERE status = 'Kosong'")->fetchColumn();
$pending_bayar  = $pdo->query("SELECT COUNT(*) FROM pembayaran WHERE status_pembayaran = 'Pending'")->fetchColumn();
$total_kamar    = $pdo->query("SELECT COUNT(*) FROM kamar")->fetchColumn();

// Poin 3: Daftar penghuni terbaru beserta status pembayarannya
$penghuni_baru = $pdo->query("SELECT p.nama, k.nomor_kamar, p.status_penghuni FROM penghuni p JOIN kamar k ON p.id_kamar = k.id_kamar ORDER BY p.id_penghuni DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Poin 2: Rekap pemasukan bulan berjalan (+ breakdown lunas/pending/belum)
$bulan_ini = date('Y-m');
$pemasukan      = $pdo->query("SELECT COALESCE(SUM(jumlah_bayar),0) FROM pembayaran WHERE status_pembayaran='Lunas' AND periode_bulan LIKE '$bulan_ini%'")->fetchColumn();
$jumlah_lunas   = $pdo->query("SELECT COUNT(*) FROM pembayaran WHERE status_pembayaran='Lunas' AND periode_bulan LIKE '$bulan_ini%'")->fetchColumn();
$jumlah_pending = $pdo->query("SELECT COUNT(*) FROM pembayaran WHERE status_pembayaran='Pending' AND periode_bulan LIKE '$bulan_ini%'")->fetchColumn();
$jumlah_belum   = $total_kamar - $jumlah_lunas - $jumlah_pending;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pemilik - Kost Mutmainah</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="dash-nav">
    <div class="logo">Kost Mutmainah</div>
    <nav>
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="penghuni.php">Penghuni</a>
        <a href="pembayaran.php">Pembayaran</a>
        <a href="booking.php">Pemesanan</a>
        <a href="../logout.php">Logout</a>
    </nav>
</div>

<div class="dashboard-section">
    <h1>Dashboard Pemilik</h1>
    <p class="sub">Ringkasan informasi dan pengelolaan Kost Mutmainah.</p>

    <div class="dash-top">
        <div class="rekap-box">
            <h3>Rekap Pembayaran</h3>
            <div class="rekap-highlight">
                <div class="rekap-highlight-label">Pemasukan Bulan Ini</div>
                <div class="rekap-highlight-value">Rp<?= number_format($pemasukan, 0, ',', '.') ?></div>
            </div>
            <div class="rekap-mini-grid">
                <div class="mini-stat mini-green">
                    <div class="mini-value"><?= $jumlah_lunas ?></div>
                    <div class="mini-label">Lunas</div>
                </div>
                <div class="mini-stat mini-yellow">
                    <div class="mini-value"><?= $jumlah_pending ?></div>
                    <div class="mini-label">Pending</div>
                </div>
                <div class="mini-stat mini-red">
                    <div class="mini-value"><?= $jumlah_belum ?></div>
                    <div class="mini-label">Belum</div>
                </div>
            </div>
        </div>

        <div class="stat-cards-vertical">
            <div class="stat-card">
                <div class="stat-icon"><img src="../icon/kamar kosong.png"></div>
                <div class="stat-label">Kamar Kosong</div>
                <div class="stat-value"><?= $kamar_kosong ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><img src="../icon/total kamar.png"></div>
                <div class="stat-label">Total Kamar</div>
                <div class="stat-value"><?= $total_kamar ?></div>
            </div>
        </div>
    </div>

    <div class="table-box">
        <h3>Penghuni Terbaru</h3>
        <table>
            <thead>
                <tr><th>Nama</th><th>Kamar</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach ($penghuni_baru as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['nama']) ?></td>
                    <td><?= htmlspecialchars($p['nomor_kamar']) ?></td>
                    <td><span class="badge <?= $p['status_penghuni'] === 'Aktif' ? 'badge-green' : 'badge-yellow' ?>"><?= $p['status_penghuni'] ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<footer>© 2026 Kost Mutmainah. All Rights Reserved.</footer>
</body>
</html>