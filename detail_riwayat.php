<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND buyer_id = ?");
$stmt->execute([$id, $_SESSION['user']['id']]);
$order = $stmt->fetch();
$productName = $order['product_name'] ?? 'Kain Tenun Sumba Asli';
$total = $order ? (float)$order['product_price'] + (float)$order['service_fee'] : 525000;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Riwayat - TitipGo</title>
    <link rel="stylesheet" href="css/buyer_feature_common.css">
    <link rel="stylesheet" href="css/detail_riwayat.css">
</head>
<body>
<main class="app">
    <?php include 'sidebar_buyer.php'; ?>

    <section class="main">
        <header class="topbar">
            <h1>↩️ Detail Riwayat Transaksi</h1>
            <div class="user-area">Hi, <?= htmlspecialchars($_SESSION['user']['name']); ?> <a class="user-icon" href="edit_profil.php">○</a></div>
        </header>

        <section class="page-content detail-layout">
            <div class="detail-card card">
                <h2>Logistik & Informasi Pengiriman</h2>
                <p>ID Transaksi: #TG-20260524-8910 • Metode: Hand-Carry (Kabin)</p>
                <div class="success-box"><b>✓ Paket Telah Diterima oleh Pembeli</b><br>Waktu Kedatangan Sesuai ETA: 24 Mei 2026, 17:45 WIB<br>Lokasi Serah Terima: Cileungsi, Bogor, Jawa Barat</div>
                <h3>Deskripsi Barang & Dokumen UMKM</h3>
                <p><b>Nama Produk</b><br><?= htmlspecialchars($productName); ?> (Pewarnaan Alami Khas Motif Tradisional NTT)</p>
                <p><b>Spesifikasi Tambahan</b><br>Ukuran: 200cm x 100cm • Kondisi: Lolos QC Room • Asuransi Keamanan Paket: Aktif</p>
                <hr>
                <h3>Rincian Komponen Pembiayaan</h3>
                <div class="cost-row"><span>Harga Asli Produk UMKM</span><b>Rp <?= number_format(max($total - 75000, 0), 0, ',', '.'); ?></b></div>
                <div class="cost-row"><span>Tips / Komisi Jasa Jastiper</span><b>Rp 75.000</b></div>
            </div>

            <aside class="summary-card card">
                <h2>Ringkasan Riwayat</h2>
                <div class="total-box"><span>Total Pengeluaran Transaksi</span><b>Rp <?= number_format($total, 0, ',', '.'); ?></b></div>
                <div class="review-box"><b>Bagikan Masukan Anda</b><br>Berikan penilaian bintang & ulasan untuk Toko, Jastiper, dan Kurir.<br><a class="btn" href="review_layanan.php">⭐ Beri Review & Rating</a></div>
            </aside>
        </section>
    </section>
</main>
</body>
</html>
