<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requirePenghuni();

$id_user = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM penghuni WHERE id_user = ?");
$stmt->execute([$id_user]);
$penghuni = $stmt->fetch(PDO::FETCH_ASSOC);

$error = '';
$success = false;

if ($penghuni && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES['bukti_transfer']['name'])) {
        $error = 'Silakan upload bukti transfer terlebih dahulu.';
    } else {
        $ext = strtolower(pathinfo($_FILES['bukti_transfer']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowed)) {
            $error = 'Format file harus JPG atau PNG.';
        } elseif ($_FILES['bukti_transfer']['size'] > 2 * 1024 * 1024) {
            $error = 'Ukuran file maksimal 2MB.';
        } else {
            $nama_file = 'bukti_' . $penghuni['id_penghuni'] . '_' . time() . '.' . $ext;
            $dir_tujuan = __DIR__ . '/../uploads/bukti_transfer';
            if (!is_dir($dir_tujuan)) {
                mkdir($dir_tujuan, 0755, true);
            }
            if (move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], $dir_tujuan . '/' . $nama_file)) {
                $stmt = $pdo->prepare(
                    "INSERT INTO pembayaran (id_penghuni, jumlah_bayar, tanggal_bayar, periode_bulan, status_pembayaran, bukti_transfer) VALUES (?, ?, NOW(), ?, 'Pending', ?)"
                );
                $stmt->execute([$penghuni['id_penghuni'], 700000, date('Y-m'), $nama_file]);
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
  <title>Pembayaran - Kost Mutmainah</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: sans-serif; background: #f3f4f6; }

    .page { min-height: calc(100vh - 52px); padding: 40px 16px; background: #f3f4f6; }
    .container { max-width: 480px; margin: 0 auto; }

    h1 { font-size: 22px; font-weight: 600; text-align: center; margin-bottom: 6px; color: #111; }
    .sub { font-size: 14px; color: #6b7280; text-align: center; margin-bottom: 28px; }

    .card {
      background: #fff; border: 1px solid #e5e7eb;
      border-radius: 12px; padding: 20px; margin-bottom: 16px;
    }

    .tagihan-card {
      background: #E8F9F3; border-radius: 8px;
      padding: 16px; margin-bottom: 16px;
    }
    .tagihan-label { font-size: 12px; color: #0B7A54; margin-bottom: 4px; }
    .tagihan-amount { font-size: 24px; font-weight: 700; color: #0B7A54; }
    .tagihan-due { font-size: 12px; color: #0B7A54; margin-top: 2px; }

    .section-label { font-size: 13px; color: #6b7280; margin-bottom: 8px; }

    .transfer-box {
      border: 1px solid #e5e7eb; border-radius: 8px;
      padding: 12px 14px; margin-bottom: 16px;
    }
    .transfer-bank { font-size: 14px; font-weight: 600; color: #111; }
    .transfer-name { font-size: 13px; color: #6b7280; margin-top: 2px; }

    .upload-area {
      border: 2px dashed #d1d5db; border-radius: 8px;
      padding: 28px 16px; text-align: center; cursor: pointer;
      transition: background 0.15s;
    }
    .upload-area:hover { background: #f9fafb; }
    .upload-icon { font-size: 32px; color: #9ca3af; margin-bottom: 8px; }
    .upload-text { font-size: 13px; color: #6b7280; margin-bottom: 12px; }

    .btn-outline {
      border: 1px solid #d1d5db; background: transparent;
      border-radius: 8px; padding: 7px 18px;
      font-size: 13px; font-weight: 500; color: #1CAF82; cursor: pointer;
    }
    .btn-outline:hover { background: #f3f4f6; }

    .filename { font-size: 12px; color: #6b7280; margin-top: 8px; }

    .btn-primary {
      width: 100%; background: #1CAF82; color: #fff;
      border: none; border-radius: 8px;
      padding: 14px; font-size: 15px; font-weight: 600;
      cursor: pointer; margin-top: 4px;
    }
    .btn-primary:hover { background: #178F6A; }

    .error-msg {
      background: #FEE2E2; color: #B91C1C;
      padding: 10px 14px; border-radius: 8px;
      font-size: 13px; margin-bottom: 16px;
    }

    footer {
      text-align: center; padding: 20px;
      font-size: 13px; color: #9ca3af;
    }
  </style>
</head>
<body>

<div class="dash-nav">
    <div class="logo">Kost Mutmainah</div>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="pembayaran.php" class="active">Pembayaran</a>
        <a href="../logout.php">Logout</a>
    </nav>
</div>

<div class="page">
  <div class="container">
    <h1>Pembayaran Kost</h1>
    <p class="sub">Upload bukti pembayaran untuk melakukan konfirmasi pembayaran kost.</p>

    <?php if ($success): ?>
      <div class="card" style="text-align:center;">
        <h2 style="color:#0B7A54;margin-bottom:8px;">Pembayaran Terkirim!</h2>
        <p class="sub" style="margin-bottom:0;">Menunggu verifikasi dari pemilik kost.</p>
      </div>
    <?php else: ?>

    <div class="card">
      <div class="tagihan-card">
        <div class="tagihan-label">Total Tagihan</div>
        <div class="tagihan-amount">Rp700.000</div>
        <div class="tagihan-due">Jatuh tempo: 10 Januari 2025</div>
      </div>

      <div class="section-label">Transfer Ke</div>
      <div class="transfer-box">
        <div class="transfer-bank">BCA – 1234567890</div>
        <div class="transfer-name">a.n Kost Mutmainah</div>
      </div>

      <?php if ($error): ?>
        <div class="error-msg"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" id="formBayar">
        <div class="section-label">Upload Bukti Pembayaran</div>
        <div class="upload-area" onclick="document.getElementById('fileInput').click()">
          <div class="upload-icon">
            <img src="../icon/upload.png" alt="upload">
          </div>
          <div class="upload-text">Upload bukti transfer pembayaran</div>
          <button class="btn-outline" type="button">Pilih File</button>
          <input type="file" name="bukti_transfer" id="fileInput" style="display:none"
                 accept=".jpg,.jpeg,.png" onchange="showFile(this)">
          <div class="filename" id="fname"></div>
        </div>

        <button class="btn-primary" type="submit" style="margin-top:16px;">Konfirmasi Pembayaran</button>
      </form>
    </div>

    <?php endif; ?>
  </div>
</div>

<footer>© 2026 Kost Mutmainah. All Rights Reserved.</footer>

<script>
function showFile(input) {
  document.getElementById('fname').textContent = input.files[0]?.name || '';
}
</script>

</body>
</html>