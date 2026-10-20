<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = 'Review berhasil dikirim. Terima kasih atas penilaian Anda.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Layanan - TitipGo</title>
    <link rel="stylesheet" href="css/buyer_feature_common.css">
    <link rel="stylesheet" href="css/review_layanan.css">
</head>
<body>
<main class="app">
    <?php include 'sidebar_buyer.php'; ?>

    <section class="main">
        <header class="topbar">
            <h1>Penilaian Kinerja Layanan</h1>
            <div class="user-area">Hi, <?= htmlspecialchars($_SESSION['user']['name']); ?> <a class="user-icon" href="edit_profil.php">○</a></div>
        </header>

        <section class="page-content">
            <form class="review-card card" method="POST">
                <?php if ($message): ?>
                    <div class="alert"><?= htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <div class="review-block">
                    <h3>1. Evaluasi Kualitas Toko UMKM Daerah</h3>
                    <div class="stars">⭐⭐⭐⭐⭐</div>
                    <input name="store_review" placeholder="Tulis ulasan produk khas daerah dari pengrajin asli di sini...">
                </div>

                <div class="review-block">
                    <h3>2. Evaluasi Kredibilitas Mitra Jastiper (Traveler)</h3>
                    <div class="stars">⭐⭐⭐⭐⭐</div>
                    <input name="traveler_review" placeholder="Tulis masukan tentang kesesuaian SOP, komunikasi, dan kejujuran belanja jastiper...">
                </div>

                <div class="review-block">
                    <h3>3. Evaluasi Kecepatan Kurir Pengiriman Domestik</h3>
                    <div class="stars">⭐⭐⭐⭐⭐</div>
                    <input name="courier_review" placeholder="Tulis ulasan tentang ketepatan waktu kurir lokal saat mengantar paket ke rumah Anda...">
                </div>

                <div class="review-actions">
                    <a class="btn btn-outline" href="riwayat_pesanan.php">Lewati</a>
                    <button class="btn" type="submit">Kirim Semua Ulasan</button>
                </div>
            </form>
        </section>
    </section>
</main>
</body>
</html>
