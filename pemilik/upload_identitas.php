<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// ambil booking milik user ini yang statusnya Menunggu Identitas
$stmt = $pdo->prepare("SELECT * FROM booking WHERE id_user = ? AND status_booking = 'Menunggu Identitas' ORDER BY tanggal DESC LIMIT 1");
$stmt->execute([$user_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $booking) {
    if (empty($_FILES['identitas']['name'])) {
        $error = 'Silakan pilih file identitas (KTP) terlebih dahulu.';
    } else {
        $ext = strtolower(pathinfo($_FILES['identitas']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array($ext, $allowed)) {
            $error = 'Format file harus JPG, PNG, atau PDF.';
        } elseif ($_FILES['identitas']['size'] > 2 * 1024 * 1024) {
            $error = 'Ukuran file maksimal 2MB.';
        } else {
            $nama_file = 'identitas_' . $booking['id_booking'] . '_' . time() . '.' . $ext;
            $tujuan = __DIR__ . '/uploads/identitas/' . $nama_file;

            if (!is_dir(__DIR__ . '/uploads/identitas')) {
                mkdir(__DIR__ . '/uploads/identitas', 0755, true);
            }

            if (move_uploaded_file($_FILES['identitas']['tmp_name'], $tujuan)) {
                $pdo->prepare("UPDATE booking SET identitas = ?, status_booking = 'Menunggu Konfirmasi' WHERE id_booking = ?")
                    ->execute([$nama_file, $booking['id_booking']]);
                $success = true;
            } else {
                $error = 'Gagal mengunggah file, coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Identitas - Kost Mutmainah</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="navbar">
    <div class="logo">Kost Mutmainah</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="kamar.php">Kamar</a>
        <a href="kontak.php">Kontak</a>
        <a href="logout.php">Logout</a>
    </nav>
</div>

<div class="booking-section">
    <h1>Upload Identitas</h1>
    <p class="sub">Pemesanan Anda sudah disetujui sementara. Silakan upload identitas (KTP) untuk melanjutkan proses konfirmasi akhir.</p>

    <div class="form-box">
        <?php if (!$booking): ?>
            <div class="error-msg">Tidak ada pemesanan yang menunggu upload identitas saat ini.</div>
            <a href="index.php" class="btn btn-primary" style="margin-top:16px;display:inline-block;">Kembali ke Beranda</a>
        <?php elseif ($success): ?>
            <div class="success-box">
                <h2>Identitas Berhasil Dikirim!</h2>
                <p>Pemilik kost akan meninjau data Anda untuk konfirmasi akhir.</p>
                <a href="index.php" class="btn btn-primary">Kembali ke Beranda</a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>File Identitas (KTP)</label>
                    <input type="file" name="identitas" accept=".jpg,.jpeg,.png,.pdf" required>
                    <small>Format: JPG, PNG, atau PDF. Maksimal 2MB.</small>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;padding:14px;">Upload Identitas</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<footer>&copy; 2026 Kost Mutmainah. All Rights Reserved.</footer>
</body>
</html>