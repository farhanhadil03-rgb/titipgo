<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$orderId = $_GET['order_id'] ?? null;

if ($orderId) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND buyer_id = ?");
    $stmt->execute([$orderId, $_SESSION['user']['id']]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE buyer_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$_SESSION['user']['id']]);
}

$order = $stmt->fetch();
$basePrice = $order ? (float)$order['product_price'] + (float)$order['service_fee'] : 1525000;
$total = $basePrice + 45000;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($order) {
        $update = $pdo->prepare("UPDATE orders SET status = 'on_delivery', delivery_option = 'private_courier' WHERE id = ? AND buyer_id = ?");
        $update->execute([$order['id'], $_SESSION['user']['id']]);
    }
    header('Location: chat_kurir.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kurir Domestik - TitipGo</title>
    <link rel="stylesheet" href="css/buyer_feature_common.css">
    <link rel="stylesheet" href="css/kurir_domestik.css">
</head>
<body>
<main class="app">
    <?php include 'sidebar_buyer.php'; ?>

    <section class="main">
        <header class="topbar">
            <h1>↩️ Atur Pengiriman Kurir Domestik</h1>
            <div class="user-area">Hi, <?= htmlspecialchars($_SESSION['user']['name']); ?> <a class="user-icon" href="edit_profil.php">○</a></div>
        </header>

        <form class="page-content courier-layout" method="POST">
            <section class="courier-card card">
                <h2>Form Penjadwalan & Alamat Kurir</h2>
                <p>Lengkapi detail instruksi berikut untuk melakukan pemanggilan armada kurir.</p>

                <label>Kapan Barang Harus Di-pickup?</label>
                <div class="two-col">
                    <input type="text" value="Jumat, 05 Juni 2026" readonly>
                    <select><option>09:00 - 11:00 WIB (Pagi)</option></select>
                </div>

                <label>Pick-up Di Mana? (Lokasi Jastiper)</label>
                <div class="info-box"><b>Bandara Internasional Soekarno-Hatta (CGK)</b><br>Terminal 3 Kedatangan Internasional, Area Pick-up Zone B<br>Tangerang, Banten • Jastiper: Andi Pratama</div>

                <label>Antar Ke Mana? (Alamat Rumah Anda)</label>
                <div class="info-box active"><b>Rumah Utama (Alamat Default Terpilih)</b><br>Jl. Bougenville No. 45, Cileungsi, Kabupaten Bogor<br>Jawa Barat, 16820 • Penerima: User</div>

                <label>Pilihan Mitra Armada Kurir</label>
                <div class="two-col">
                    <label class="courier-option active">⚡ <b>Kurir Instant Sameday</b><small>Estimasi tiba hari yang sama</small></label>
                    <label class="courier-option">📦 <b>Logistik Reguler Ekspedisi</b><small>Estimasi tiba dalam 1-2 hari kerja</small></label>
                </div>
            </section>

            <aside class="summary-card card">
                <h2>Rincian Pembayaran Akhir</h2>
                <div class="summary-row"><span>Harga Barang Titipan</span><b>Rp <?= number_format($basePrice, 0, ',', '.'); ?></b></div>
                <div class="summary-row"><span>Biaya Kurir Domestik</span><b>Rp 45.000</b></div>
                <div class="summary-row green"><span>Biaya Admin Lapisan Escrow</span><b>Gratis<br>(Promo Plus)</b></div>
                <hr>
                <div class="total-row"><span>Total Tagihan Baru</span><b>Rp <?= number_format($total, 0, ',', '.'); ?></b></div>
                <div class="protect-box">🛡️ <b>Proteksi Penuh Diaktifkan</b><br>Seluruh dana belanja dan biaya kirim kurir akan disimpan aman di escrow sampai paket sampai.</div>
                <button class="btn" type="submit">Pesan & Bayar Rp <?= number_format($total, 0, ',', '.'); ?></button>
            </aside>
        </form>
    </section>
</main>
</body>
</html>
