<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Handle login
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        $redirect_target = $_POST['redirect'] ?? '';
        if ($redirect_target) {
            header('Location: ' . $redirect_target);
        } elseif ($user['role'] === 'pemilik') {
            header('Location: pemilik/dashboard.php');
        } else {
            header('Location: penghuni/dashboard.php');
        }
        exit;
    } else {
        $login_error = 'Username atau password salah.';
    }
}


$info = $pdo->query("SELECT * FROM informasi_kost LIMIT 1")->fetch(PDO::FETCH_ASSOC);


$kosong = $pdo->query("SELECT COUNT(*) FROM kamar WHERE status = 'Kosong'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kost Mutmainah</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <div class="logo">Kost Mutmainah</div>
    <nav>
        <a href="index.php" class="active">Home</a>
        <a href="kamar.php">Kamar</a>
        <a href="kontak.php">Kontak</a>
        <?php if (isLoggedIn()): ?>
            <?php if (isPemilik()): ?>
                <a href="pemilik/dashboard.php">Dashboard</a>
            <?php else: ?>
                <a href="penghuni/dashboard.php">Dashboard</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="#" class="btn-login" onclick="openModal()">Login</a>
        <?php endif; ?>
    </nav>
</div>


<div class="hero">
    <div class="hero-text">
        <h4>Selamat Datang di</h4>
        <h1><?= htmlspecialchars($info['nama_kost'] ?? 'Kost Mutmainah') ?></h1>
        <p><?= htmlspecialchars($info['deskripsi'] ?? 'Kost nyaman dengan fasilitas lengkap, lingkungan bersih, dan lokasi strategis untuk mahasiswa maupun pekerja.') ?></p>
        <div class="hero-btns">
            <a href="booking.php" class="btn btn-primary">Pesan Sekarang</a>
            <a href="kontak.php" class="btn btn-outline">Hubungi Owner</a>
        </div>
    </div>
    <div class="hero-img">
        <?php if (!empty($info['foto'])): ?>
            <img src="uploads/<?= htmlspecialchars($info['foto']) ?>" alt="Foto Kost">
        <?php else: ?>
            Foto Kost
        <?php endif; ?>
    </div>
</div>

<!-- INFO CARDS -->
<div class="info-section">
    <h2>Informasi Kost</h2>
<div class="cards">
    <div class="card">
        <div class="icon"><img src="icon/Fasilitas.png" alt="Fasilitas"></div>
        <h3>Fasilitas</h3>
        <p>Kasur, lemari, WiFi, kamar mandi dalam, dan area parkir.</p>
    </div>
    <div class="card">
        <div class="icon"><img src="icon/harga.png" alt="Harga"></div>
        <h3>Harga</h3>
        <p>Mulai dari Rp700.000 per bulan dengan fasilitas lengkap.</p>
    </div>
    <div class="card">
        <div class="icon"><img src="icon/ketersediaan.png" alt="Ketersediaan"></div>
        <h3>Ketersediaan</h3>
        <p>Tersedia <?= $kosong ?> kamar kosong yang siap ditempati.</p>
    </div>
</div>
</div>

<footer>© 2026 Kost Mutmainah. All Rights Reserved.</footer>


<div class="modal-overlay <?= ($login_error || isset($_GET['login'])) ? 'active' : '' ?>" id="loginModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal()">✕</button>
        <h2>Login</h2>
        <p class="sub">Masukkan username dan password Anda</p>
        <?php if ($login_error): ?>
            <div class="error-msg"><?= $login_error ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect_url'] ?? '') ?>">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Masukkan username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%">Login</button>

        </form>
        <p class="sub" style="margin-top:16px;">
    Belum punya akun? <a href="register.php?redirect_url=<?= urlencode($_GET['redirect_url'] ?? '') ?>">Daftar di sini</a>
</p>
    </div>
</div>

<script>
function openModal() { document.getElementById('loginModal').classList.add('active'); }
function closeModal() { document.getElementById('loginModal').classList.remove('active'); }
document.getElementById('loginModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>