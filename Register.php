<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    if (!$username || !$password || !$konfirmasi) {
        $error = 'Semua kolom wajib diisi.';
    } elseif (strlen($username) < 4) {
        $error = 'Username minimal 4 karakter.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // cek username udah dipakai apa belum
        $stmt = $pdo->prepare("SELECT id_user FROM user WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Username sudah digunakan, coba yang lain.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO user (username, password, role) VALUES (?, ?, 'penghuni')");
            $stmt->execute([$username, $hashed]);
            $success = true;
        }
    }
}

// kalau ada redirect_url yang dibawa dari booking.php, terusin ke halaman login
$redirect_url = $_GET['redirect_url'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Kost Mutmainah</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="navbar">
    <div class="logo">Kost Mutmainah</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="kamar.php">Kamar</a>
        <a href="kontak.php">Kontak</a>
        <a href="index.php?login=1" class="btn-login">Login</a>
    </nav>
</div>

<div class="booking-section">
    <h1>Daftar Akun</h1>
    <p class="sub">Buat akun untuk mulai memesan kamar di Kost Mutmainah.</p>
    <div class="form-box">
        <?php if ($success): ?>
            <div class="success-box">
                <h2>Registrasi Berhasil!</h2>
                <p>Akun kamu sudah dibuat. Silakan login untuk melanjutkan.</p>
                <a href="index.php?login=1&redirect_url=<?= urlencode($redirect_url) ?>" class="btn btn-primary">Login Sekarang</a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Masukkan username" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Minimal 6 karakter" required>
                </div>
                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <input type="password" name="konfirmasi_password" placeholder="Ulangi password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;padding:14px;">Daftar</button>
            </form>
            <p class="sub" style="margin-top:16px;">
                Sudah punya akun? <a href="index.php?login=1&redirect_url=<?= urlencode($redirect_url) ?>">Login di sini</a>
            </p>
        <?php endif; ?>
    </div>
</div>

<footer>&copy; 2026 Kost Mutmainah. All Rights Reserved.</footer>
</body>
</html>