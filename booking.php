<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$id_kamar = $_GET['id_kamar'] ?? null;
if (!$id_kamar) {
    header('Location: kamar.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM kamar WHERE id_kamar = ?");
$stmt->execute([$id_kamar]);
$kamar = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$kamar) {
    header('Location: kamar.php');
    exit;
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $nomor_hp = trim($_POST['nomor_hp'] ?? '');
    $tanggal_mulai_sewa = trim($_POST['tanggal_mulai_sewa'] ?? '');

    if (!isLoggedIn()) {
        $_SESSION['pending_booking'] = [
            'id_kamar' => $id_kamar,
            'nama' => $nama,
            'nomor_hp' => $nomor_hp,
            'tanggal_mulai_sewa' => $tanggal_mulai_sewa,
        ];
        $redirect_url = 'booking.php?id_kamar=' . urlencode($id_kamar);
        header('Location: index.php?login=1&redirect_url=' . urlencode($redirect_url));
        exit;
    }

    if (!$nama || !$nomor_hp || !$tanggal_mulai_sewa) {
        $error = 'Semua kolom wajib diisi.';
    } elseif (strtotime($tanggal_mulai_sewa) < strtotime(date('Y-m-d'))) {
        $error = 'Tanggal mulai sewa tidak boleh sebelum hari ini.';
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO booking (id_user, id_kamar, nama, nomor_hp, tanggal, tanggal_mulai_sewa, status_booking) VALUES (?, ?, ?, ?, ?, ?, 'Menunggu Persetujuan')"
        );
        $stmt->execute([$_SESSION['user_id'], $id_kamar, $nama, $nomor_hp, date('Y-m-d'), $tanggal_mulai_sewa]);
        unset($_SESSION['pending_booking']);
        $success = true;
    }
}

$prefill = $_SESSION['pending_booking'] ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan Kamar - Kost Mutmainah</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="navbar">
    <div class="logo">Kost Mutmainah</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="kamar.php">Kamar</a>
        <a href="kontak.php">Kontak</a>
        <?php if (isLoggedIn()): ?>
            <?php if (isPemilik()): ?>
                <a href="pemilik/dashboard.php">Dashboard</a>
            <?php else: ?>
                <a href="penghuni/dashboard.php">Dashboard</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        <?php endif; ?>
    </nav>
</div>
<div class="booking-section">
    <h1>Pesan Kamar</h1>
    <p class="sub">Lengkapi data berikut untuk mengajukan pemesanan kamar.</p>
    <div class="form-box">
        <?php if ($success): ?>
        <div class="success-box">
            <div class="icon-success">
                <img src="icon/ketersediaan.png" alt="Booking Berhasil" width="50" height="50">
            </div>
            <h2>Pemesanan Terkirim!</h2>
            <p>Permintaan pemesanan kamu sedang ditinjau pemilik kost. Kamu akan diminta upload identitas setelah disetujui sementara.</p>
            <a href="index.php" class="btn btn-primary">Kembali Ke Beranda</a>
        </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <div class="form-group">
                <label>Kamar Dipilih</label>
                <input type="text" value="<?= htmlspecialchars($kamar['nama_kamar'] ?? $kamar['id_kamar']) ?>" disabled>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" value="<?= htmlspecialchars($prefill['nama'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>No. HP / WhatsApp</label>
                    <input type="text" name="nomor_hp" value="<?= htmlspecialchars($prefill['nomor_hp'] ?? '') ?>" placeholder="08xxxxxxxxxx" required>
                </div>
                <div class="form-group">
                    <label>Tanggal Mulai Sewa</label>
                    <input type="date" name="tanggal_mulai_sewa" value="<?= htmlspecialchars($prefill['tanggal_mulai_sewa'] ?? '') ?>" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;padding:14px;">Ajukan Pemesanan</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<footer>&copy; 2026 Kost Mutmainah. All Rights Reserved.</footer>
</body>
</html>