<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requirePemilik();

$search = trim($_GET['q'] ?? '');
if ($search) {
    $stmt = $pdo->prepare("SELECT p.*, k.nomor_kamar, k.harga_sewa, u.username,
        TIMESTAMPDIFF(MONTH, p.tanggal_masuk, NOW()) as lama_sewa,
        (SELECT status_pembayaran FROM pembayaran WHERE id_penghuni = p.id_penghuni ORDER BY tanggal_bayar DESC LIMIT 1) as status_bayar
FROM penghuni p LEFT JOIN kamar k ON p.id_kamar = k.id_kamar LEFT JOIN user u ON p.id_user = u.id_user
        WHERE p.nama LIKE ? ORDER BY p.id_penghuni DESC");
    $stmt->execute(["%$search%"]);
} else {
    $stmt = $pdo->query("SELECT p.*, k.nomor_kamar, k.harga_sewa, u.username,
        TIMESTAMPDIFF(MONTH, p.tanggal_masuk, NOW()) as lama_sewa,
        (SELECT status_pembayaran FROM pembayaran WHERE id_penghuni = p.id_penghuni ORDER BY tanggal_bayar DESC LIMIT 1) as status_bayar
FROM penghuni p LEFT JOIN kamar k ON p.id_kamar = k.id_kamar LEFT JOIN user u ON p.id_user = u.id_user
        ORDER BY p.id_penghuni DESC");
}
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Penghuni - Kost Mutmainah</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="dash-nav">
    <div class="logo">Kost Mutmainah</div>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="penghuni.php" class="active">Penghuni</a>
        <a href="pembayaran.php">Pembayaran</a>
        <a href="booking.php">Pemesanan</a>
        <a href="../logout.php">Logout</a>
    </nav>
</div>

<div class="dashboard-section">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;">
        <div>
            <h1>Data Penghuni</h1>
            <p class="sub">Informasi data penghuni Kost Mutmainah.</p>
        </div>
        <form method="GET">
            <input type="text" name="q" class="search-input" placeholder="Cari penghuni..." value="<?= htmlspecialchars($search) ?>">
        </form>
    </div>

    <div class="table-box">
        <h3>List Penghuni</h3>
        <table>
            <thead>
                <tr>
                    <th>Nama</th><th>Nomor HP</th><th>Identitas</th><th>Kamar</th>
                    <th>Status Pembayaran</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($list): ?>
                    <?php foreach ($list as $p): ?>
                    <?php
                    $wa = preg_replace('/[^0-9]/', '', $p['nomor_hp']);
                    if (substr($wa, 0, 1) === '0') $wa = '62' . substr($wa, 1);
                    $pesan = urlencode("Halo " . $p['nama'] . ", ini adalah reminder pembayaran sewa kost bulan " . date('F Y') . ". Mohon segera melakukan pembayaran. Terima kasih.");
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($p['nama']) ?></td>
                        <td><?= htmlspecialchars($p['nomor_hp']) ?></td>
                        <td>
    <?php if (!empty($p['identitas'])): ?>
        <button class="bukti-btn" onclick="window.open('../uploads/identitas/<?= $p['identitas'] ?>')">
            <?= htmlspecialchars($p['identitas']) ?>
        </button>
    <?php else: ?> - <?php endif; ?>
</td>
                        <td><?= htmlspecialchars($p['nomor_kamar']) ?></td>
                    
                        <td><span class="badge <?= $p['status_bayar'] === 'Lunas' ? 'badge-green' : 'badge-yellow' ?>"><?= $p['status_bayar'] ?? 'Belum Bayar' ?></span></td>
                        <td style="display:flex;gap:6px;">
                            <?php if ($p['status_bayar'] !== 'Lunas'): ?>
                            <a href="https://wa.me/<?= $wa ?>?text=<?= $pesan ?>" target="_blank" class="btn btn-success btn-sm">Kirim Reminder WA</a>
                            <?php endif; ?>
      
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;color:#aaa;">Tidak ada data penghuni</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<footer>© 2026 Kost Mutmainah. All Rights Reserved.</footer>
</body>
</html>
