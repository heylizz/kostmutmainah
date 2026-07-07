<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requirePemilik();

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pembayaran = intval($_POST['id_pembayaran']);
    $aksi = $_POST['aksi'];

    if ($aksi === 'approve') {
        $pdo->prepare("UPDATE pembayaran SET status_pembayaran = 'Lunas' WHERE id_pembayaran = ?")
            ->execute([$id_pembayaran]);
    } elseif ($aksi === 'reject') {
        $pdo->prepare("UPDATE pembayaran SET status_pembayaran = 'Ditolak' WHERE id_pembayaran = ?")
            ->execute([$id_pembayaran]);
    }

    header('Location: pembayaran.php');
    exit;
}

// Ambil semua pembayaran berstatus Pending
$list = $pdo->query("
    SELECT p.*, pn.nama, k.nomor_kamar
    FROM pembayaran p
    JOIN penghuni pn ON p.id_penghuni = pn.id_penghuni
    LEFT JOIN kamar k ON pn.id_kamar = k.id_kamar
    WHERE p.status_pembayaran = 'Pending'
    ORDER BY p.tanggal_bayar DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Pembayaran - Kost Mutmainah</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="dash-nav">
    <div class="logo">Kost Mutmainah</div>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="penghuni.php">Penghuni</a>
        <a href="pembayaran.php" class="active">Pembayaran</a>
        <a href="booking.php">Pemesanan</a>
        <a href="../logout.php">Logout</a>
    </nav>
</div>

<div class="dashboard-section">
    <h1>Validasi Pembayaran</h1>
    <p class="sub">Periksa dan validasi pembayaran penghuni kost.</p>

    <div class="table-box">
        <h3>Pembayaran Pending</h3>
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Kamar</th>
                    <th>Bukti Transfer</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($list): ?>
                    <?php foreach ($list as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['nama']) ?></td>
                        <td><?= htmlspecialchars($p['nomor_kamar'] ?? '-') ?></td>
                        <td>
                            <?php if ($p['bukti_transfer']): ?>
                                <button type="button" class="bukti-btn" onclick="window.open('../uploads/bukti_transfer/<?= htmlspecialchars($p['bukti_transfer']) ?>')">
                                    Bukti
                                </button>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-yellow"><?= htmlspecialchars($p['status_pembayaran']) ?></span></td>
                        <td style="display:flex;gap:8px;">
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="id_pembayaran" value="<?= $p['id_pembayaran'] ?>">
                                <input type="hidden" name="aksi" value="approve">
                                <button type="submit" class="btn btn-success btn-sm">Approve</button>
                            </form>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="id_pembayaran" value="<?= $p['id_pembayaran'] ?>">
                                <input type="hidden" name="aksi" value="reject">
                                <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center;color:#aaa;">Tidak ada pembayaran pending</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<footer>© 2026 Kost Mutmainah. All Rights Reserved.</footer>
</body>
</html>