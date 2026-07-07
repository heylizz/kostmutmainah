<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requirePemilik();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_booking = intval($_POST['id_booking']);
    $aksi = $_POST['aksi'];
    $tahap = $_POST['tahap']; // 'awal' atau 'final'

    $stmt = $pdo->prepare("SELECT b.*, k.nomor_kamar FROM booking b LEFT JOIN kamar k ON b.id_kamar = k.id_kamar WHERE b.id_booking = ?");
    $stmt->execute([$id_booking]);
    $bk = $stmt->fetch(PDO::FETCH_ASSOC);

    $wa = preg_replace('/[^0-9]/', '', $bk['nomor_hp']);
    if (substr($wa, 0, 1) === '0') $wa = '62' . substr($wa, 1);

    if ($tahap === 'awal' && $aksi === 'setuju') {
        // Persetujuan sementara: assign kamar, minta upload identitas
        $nomor_kamar_input = intval($_POST['nomor_kamar_' . $id_booking] ?? 0);
        if ($nomor_kamar_input > 0) {
            $cekKamar = $pdo->prepare("SELECT id_kamar FROM kamar WHERE nomor_kamar = ?");
            $cekKamar->execute([$nomor_kamar_input]);
            $kamarData = $cekKamar->fetch(PDO::FETCH_ASSOC);
            if ($kamarData) {
                $pdo->prepare("UPDATE booking SET id_kamar = ? WHERE id_booking = ?")
                    ->execute([$kamarData['id_kamar'], $id_booking]);
            }
        }
        $pdo->prepare("UPDATE booking SET status_booking = 'Menunggu Identitas' WHERE id_booking = ?")
            ->execute([$id_booking]);

        $pesan = urlencode("Halo " . $bk['nama'] . ", pemesanan kamar Anda disetujui SEMENTARA. Silakan upload identitas (KTP) di akun Anda untuk melanjutkan proses. Terima kasih - Kost Mutmainah");
        header("Location: https://wa.me/$wa?text=$pesan");
        exit;

    } elseif ($tahap === 'final' && $aksi === 'setuju') {
        // Konfirmasi akhir: setelah identitas di-upload
        $pdo->prepare("UPDATE booking SET status_booking = 'Disetujui' WHERE id_booking = ?")
            ->execute([$id_booking]);

        if ($bk['id_kamar']) {
            $cek = $pdo->prepare("SELECT id_penghuni FROM penghuni WHERE nomor_hp = ?");
            $cek->execute([$bk['nomor_hp']]);
if (!$cek->fetch()) {
    $pdo->prepare("INSERT INTO penghuni (id_user, nama, nomor_hp, id_kamar, identitas, tanggal_masuk, status_penghuni) VALUES (?,?,?,?,?,NOW(),'Aktif')")
        ->execute([$bk['id_user'], $bk['nama'], $bk['nomor_hp'], $bk['id_kamar'], $bk['identitas']]);
}
            $pdo->prepare("UPDATE kamar SET status = 'Terisi' WHERE id_kamar = ?")
                ->execute([$bk['id_kamar']]);
        }

        $pesan = urlencode("Halo " . $bk['nama'] . ", booking kamar " . $bk['nomor_kamar'] . " Anda telah DISETUJUI FINAL. Selamat bergabung di Kost Mutmainah!");
        header("Location: https://wa.me/$wa?text=$pesan");
        exit;

    } elseif ($aksi === 'tolak') {
        $pdo->prepare("UPDATE booking SET status_booking = 'Ditolak' WHERE id_booking = ?")
            ->execute([$id_booking]);

        $pesan = urlencode("Halo " . $bk['nama'] . ", maaf pemesanan kamar Anda DITOLAK. Silakan hubungi kami untuk info lebih lanjut. Terima kasih - Kost Mutmainah");
        header("Location: https://wa.me/$wa?text=$pesan");
        exit;
    }
    exit;
}

// Tahap 1: baru diajukan, belum di-assign kamar
$list_awal = $pdo->query("
    SELECT b.*, k.nomor_kamar
    FROM booking b
    LEFT JOIN kamar k ON b.id_kamar = k.id_kamar
    WHERE b.status_booking='Menunggu Persetujuan'
    ORDER BY b.tanggal DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Tahap 2: sudah upload identitas, tunggu konfirmasi akhir
$list_final = $pdo->query("
    SELECT b.*, k.nomor_kamar
    FROM booking b
    LEFT JOIN kamar k ON b.id_kamar = k.id_kamar
    WHERE b.status_booking='Menunggu Konfirmasi'
    ORDER BY b.tanggal DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pemesanan - Kost Mutmainah</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="dash-nav">
    <div class="logo">Kost Mutmainah</div>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="penghuni.php">Penghuni</a>
        <a href="pembayaran.php">Pembayaran</a>
        <a href="booking.php" class="active">Pemesanan</a>
        <a href="../logout.php">Logout</a>
    </nav>
</div>

<div class="dashboard-section">
    <h1>Kelola Pemesanan</h1>
    <p class="sub">Tinjau dan tindak lanjuti permintaan pemesanan calon penghuni.</p>

    <div class="table-box">
        <h3>Tahap 1 — Pengajuan Baru (Persetujuan Sementara)</h3>
        <table>
            <thead>
                <tr><th>Nama</th><th>No. HP</th><th>Tanggal</th><th>No. Kamar</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php if ($list_awal): ?>
                    <?php foreach ($list_awal as $b): ?>
                    <tr>
                        <td><?= htmlspecialchars($b['nama']) ?></td>
                        <td><?= htmlspecialchars($b['nomor_hp']) ?></td>
                        <td><?= date('d M Y', strtotime($b['tanggal'])) ?></td>
                        <td>
                            <input type="number" name="nomor_kamar_<?= $b['id_booking'] ?>"
                                   form="form_setuju_<?= $b['id_booking'] ?>"
                                   value="<?= htmlspecialchars($b['nomor_kamar'] ?? '') ?>"
                                   min="1" placeholder="No. Kamar"
                                   style="width:90px;padding:6px 10px;border:1px solid #ddd;border-radius:8px;">
                        </td>
                        <td style="display:flex;gap:8px;">
                            <form method="POST" id="form_setuju_<?= $b['id_booking'] ?>" style="display:inline">
                                <input type="hidden" name="id_booking" value="<?= $b['id_booking'] ?>">
                                <input type="hidden" name="aksi" value="setuju">
                                <input type="hidden" name="tahap" value="awal">
                                <button type="submit" class="btn btn-success btn-sm">Setuju Sementara & WA</button>
                            </form>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="id_booking" value="<?= $b['id_booking'] ?>">
                                <input type="hidden" name="aksi" value="tolak">
                                <input type="hidden" name="tahap" value="awal">
                                <button type="submit" class="btn btn-danger btn-sm">Tolak & WA</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center;color:#aaa;">Tidak ada pengajuan baru</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-box" style="margin-top:24px;">
        <h3>Tahap 2 — Sudah Upload Identitas (Konfirmasi Akhir)</h3>
        <table>
            <thead>
                <tr><th>Nama</th><th>No. HP</th><th>Identitas</th><th>Kamar</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php if ($list_final): ?>
                    <?php foreach ($list_final as $b): ?>
                    <tr>
                        <td><?= htmlspecialchars($b['nama']) ?></td>
                        <td><?= htmlspecialchars($b['nomor_hp']) ?></td>
                        <td>
                            <?php if ($b['identitas']): ?>
                                <button class="bukti-btn" onclick="window.open('../uploads/identitas/<?= $b['identitas'] ?>')">
                                    Lihat File
                                </button>
                            <?php else: ?> - <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($b['nomor_kamar'] ?? '-') ?></td>
                        <td style="display:flex;gap:8px;">
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="id_booking" value="<?= $b['id_booking'] ?>">
                                <input type="hidden" name="aksi" value="setuju">
                                <input type="hidden" name="tahap" value="final">
                                <button type="submit" class="btn btn-success btn-sm">Setuju Final & WA</button>
                            </form>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="id_booking" value="<?= $b['id_booking'] ?>">
                                <input type="hidden" name="aksi" value="tolak">
                                <input type="hidden" name="tahap" value="final">
                                <button type="submit" class="btn btn-danger btn-sm">Tolak & WA</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center;color:#aaa;">Belum ada yang upload identitas</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<footer>© 2026 Kost Mutmainah. All Rights Reserved.</footer>
</body>
</html>