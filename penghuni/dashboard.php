<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requirePenghuni();

$id_user = $_SESSION['user_id'];

// Cek dulu apakah sudah jadi penghuni resmi
$stmt = $pdo->prepare("SELECT p.*, k.nomor_kamar, k.harga_sewa FROM penghuni p JOIN kamar k ON p.id_kamar = k.id_kamar WHERE p.id_user = ?");
$stmt->execute([$id_user]);
$penghuni = $stmt->fetch(PDO::FETCH_ASSOC);

// Kalau belum, cek status booking terakhirnya
$booking = null;
if (!$penghuni) {
    $stmt0 = $pdo->prepare("SELECT * FROM booking WHERE id_user = ? ORDER BY tanggal DESC LIMIT 1");
    $stmt0->execute([$id_user]);
    $booking = $stmt0->fetch(PDO::FETCH_ASSOC);
}

// Handle upload identitas (kalau booking statusnya Menunggu Identitas)
$upload_error = '';
$upload_success = false;
if ($booking && $booking['status_booking'] === 'Menunggu Identitas' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES['identitas']['name'])) {
        $upload_error = 'Silakan pilih file identitas terlebih dahulu.';
    } else {
        $ext = strtolower(pathinfo($_FILES['identitas']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array($ext, $allowed)) {
            $upload_error = 'Format file harus JPG, PNG, atau PDF.';
        } elseif ($_FILES['identitas']['size'] > 2 * 1024 * 1024) {
            $upload_error = 'Ukuran file maksimal 2MB.';
        } else {
            $nama_file = 'identitas_' . $booking['id_booking'] . '_' . time() . '.' . $ext;
            $dir_tujuan = __DIR__ . '/../uploads/identitas';
            if (!is_dir($dir_tujuan)) {
                mkdir($dir_tujuan, 0755, true);
            }
            if (move_uploaded_file($_FILES['identitas']['tmp_name'], $dir_tujuan . '/' . $nama_file)) {
                $pdo->prepare("UPDATE booking SET identitas = ?, status_booking = 'Menunggu Konfirmasi' WHERE id_booking = ?")
                    ->execute([$nama_file, $booking['id_booking']]);
                $upload_success = true;
                $booking['status_booking'] = 'Menunggu Konfirmasi'; // refresh tampilan tanpa reload
            } else {
                $upload_error = 'Gagal mengunggah file, coba lagi.';
            }
        }
    }
}

// Histori pembayaran (cuma kalau udah jadi penghuni resmi)
$histori = [];
$reminder_hari = null;
if ($penghuni) {
    $stmt2 = $pdo->prepare("SELECT * FROM pembayaran WHERE id_penghuni = ? ORDER BY tanggal_bayar DESC LIMIT 5");
    $stmt2->execute([$penghuni['id_penghuni']]);
    $histori = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $bulan_ini = date('Y-m');
    $stmt3 = $pdo->prepare("SELECT * FROM pembayaran WHERE id_penghuni = ? AND periode_bulan LIKE ? ORDER BY tanggal_bayar DESC LIMIT 1");
    $stmt3->execute([$penghuni['id_penghuni'], $bulan_ini . '%']);
    $bayar_bulan = $stmt3->fetch(PDO::FETCH_ASSOC);

    $tgl_jatuh = date('Y-m-10');
    $hari_ini = date('Y-m-d');
    $diff = (strtotime($tgl_jatuh) - strtotime($hari_ini)) / 86400;
    $reminder_hari = intval($diff);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Penghuni - Kost Mutmainah</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="dash-nav">
    <div class="logo">Kost Mutmainah</div>
    <nav>
        <a href="dashboard.php" class="active">Dashboard</a>
        <?php if ($penghuni): ?>
            <a href="pembayaran.php">Pembayaran</a>
        <?php endif; ?>
        <a href="../logout.php">Logout</a>
    </nav>
</div>

<div class="dashboard-section">
    <h1>Dashboard Penghuni</h1>
    <p class="sub">Informasi status sewa dan pembayaran penghuni kost.</p>

    <?php if (!$penghuni && !$booking): ?>
        <!-- Belum pernah booking sama sekali -->
        <div class="table-box">
            <p style="text-align:center;color:#aaa;padding:24px 0;">Kamu belum memiliki pemesanan kamar.</p>
            <div style="text-align:center;">
                <a href="../kamar.php" class="btn btn-primary">Lihat Kamar</a>
            </div>
        </div>

    <?php elseif (!$penghuni && $booking): ?>
        <!-- Masih dalam proses booking -->
        <div class="table-box">
            <h3>Status Pemesanan</h3>
            <?php if ($booking['status_booking'] === 'Menunggu Persetujuan'): ?>
                <p>Pemesanan kamu sedang ditinjau oleh pemilik kost. Mohon tunggu konfirmasi.</p>

            <?php elseif ($booking['status_booking'] === 'Menunggu Identitas'): ?>
                <p>Pemesanan kamu sudah disetujui sementara. Silakan upload identitas (KTP) untuk melanjutkan.</p>
                <?php if ($upload_success): ?>
                    <div class="success-box">
                        <h2>Identitas Berhasil Dikirim!</h2>
                        <p>Pemilik kost akan meninjau untuk konfirmasi akhir.</p>
                    </div>
                <?php else: ?>
                    <?php if ($upload_error): ?>
                        <div class="error-msg"><?= htmlspecialchars($upload_error) ?></div>
                    <?php endif; ?>
                    <form method="POST" enctype="multipart/form-data" style="margin-top:16px;">
                        <div class="form-group">
                            <label>File Identitas (KTP)</label>
                            <input type="file" name="identitas" accept=".jpg,.jpeg,.png,.pdf" required>
                            <small>Format: JPG, PNG, atau PDF. Maksimal 2MB.</small>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width:100%;padding:14px;">Upload Identitas</button>
                    </form>
                <?php endif; ?>

            <?php elseif ($booking['status_booking'] === 'Menunggu Konfirmasi'): ?>
                <p>Identitas kamu sudah diterima. Pemilik kost sedang melakukan konfirmasi akhir.</p>

            <?php elseif ($booking['status_booking'] === 'Ditolak'): ?>
                <p>Mohon maaf, pemesanan kamu ditolak oleh pemilik kost.</p>
                <a href="../kamar.php" class="btn btn-primary" style="margin-top:12px;display:inline-block;">Cari Kamar Lain</a>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- Sudah jadi penghuni resmi -->
        <div class="stat-cards">
            <div class="stat-card">
                <div class="stat-icon"><img src="../icon/status sewa.png" alt="upload"></div>
                <div class="stat-label">Status Sewa</div>
                <div class="stat-value">Aktif</div>
                <span class="badge badge-green">Sedang Menempati</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><img src="../icon/pembayaran.png" alt="upload"></div>
                <div class="stat-label">Status Pembayaran</div>
                <div class="stat-value"><?= isset($bayar_bulan) && $bayar_bulan ? $bayar_bulan['status_pembayaran'] : 'Belum Bayar' ?></div>
                <?php if (isset($bayar_bulan) && $bayar_bulan): ?>
                    <span class="badge badge-blue">Bulan <?= date('F', strtotime($bayar_bulan['tanggal_bayar'])) ?></span>
                <?php endif; ?>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><img src="../icon/reminder.png" alt="upload"></div>
                <div class="stat-label">Reminder Pembayaran</div>
                <div class="stat-value"><?= $reminder_hari !== null ? abs($reminder_hari) . ' Hari ' . ($reminder_hari < 0 ? 'Lalu' : 'Lagi') : '-' ?></div>
                <span class="badge badge-yellow">Jatuh Tempo</span>
            </div>
        </div>

        <div class="table-box">
            <h3>Histori Pembayaran</h3>
            <table>
                <thead>
                    <tr><th>Bulan</th><th>Tanggal</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php if ($histori): ?>
                        <?php foreach ($histori as $h): ?>
                        <tr>
                            <td><?= htmlspecialchars($h['periode_bulan']) ?></td>
                            <td><?= date('d F Y', strtotime($h['tanggal_bayar'])) ?></td>
                            <td><span class="badge <?= $h['status_pembayaran'] === 'Lunas' ? 'badge-green' : 'badge-yellow' ?>"><?= $h['status_pembayaran'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center;color:#aaa;">Belum ada histori pembayaran</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<footer>© 2026 Kost Mutmainah. All Rights Reserved.</footer>
</body>
</html>