<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$successMessage = '';
$errorMessage = '';

$subscriptionStmt = $pdo->prepare("
    SELECT *
    FROM subscriptions
    WHERE user_id = ?
    AND status = 'active'
    AND end_date >= CURDATE()
    ORDER BY id DESC
    LIMIT 1
");
$subscriptionStmt->execute([$_SESSION['user']['id']]);
$currentSubscription = $subscriptionStmt->fetch();

if (isset($_GET['success']) && $_GET['success'] === '1') {
    $successMessage = 'Selamat! Akun Anda berhasil menjadi Jastip Plus Premium.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jastip Plus Premium - TitipGo</title>
    <link rel="stylesheet" href="css/buyer_feature_common.css">
    <link rel="stylesheet" href="css/jastip_plus.css">
</head>
<body>
<main class="premium-app">
    <aside class="premium-sidebar">
        <div class="brand">TitipGo</div>
        <nav class="premium-menu">
            <a class="active" href="jastip_plus.php">🔥 Jastip Plus Premium</a>
            <a href="buyer.php">↩️ Kembali ke Beranda</a>
            <a href="request_manual.php">🚕 Request Manual</a>
            <a href="qc_room.php">📦 Lacak & QC Room</a>
            <a href="checkout_pembayaran.php">💳 Checkout Pembayaran</a>
            <a href="profil_traveler.php">👤 Profil Traveler</a>
            <a href="riwayat_pesanan.php">📜 Riwayat Pesanan</a>
            <a href="edit_profil_buyer.php">⚙️ Edit Profil & Sandi</a>
        </nav>
    </aside>

    <section class="premium-main">
        <div class="top-user">
            Hi, <?= htmlspecialchars($_SESSION['user']['name']); ?>
            <a class="profile-icon" href="edit_profil_buyer.php">◎</a>
        </div>

        <p class="eyebrow">UPGRADE AKUNMU</p>
        <h1>Nikmati Pengalaman Jastip Tanpa Batas</h1>
        <p class="premium-desc">
            Dapatkan prioritas matching tercepat, diskon biaya layanan platform,
            dan perlindungan VIP untuk setiap transaksi-mu.
        </p>

        <?php if ($successMessage): ?>
            <div class="premium-alert success-alert">
                <?= htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="premium-alert error-alert">
                <?= htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($currentSubscription): ?>
            <div class="premium-status">
                <div>
                    <h3>⭐ Premium Aktif</h3>
                    <p>Paket: <?= htmlspecialchars($currentSubscription['package_name']); ?></p>
                    <p>Berlaku sampai: <?= date('d M Y', strtotime($currentSubscription['end_date'])); ?></p>
                </div>
                <span>ACTIVE</span>
            </div>
        <?php endif; ?>

        <div class="plans">
            <article class="plan-card">
                <h2>Paket Bulanan</h2>
                <h3>Rp 49.000 <span>bln</span></h3>
                <hr>
                <p>✓ Prioritas Matching Traveler</p>
                <p>✓ Diskon Fee Platform 50%</p>
                <p>✓ Layanan Bantuan VIP (Dipercepat)</p>

                <a class="btn muted-btn" href="jastip_plus_payment.php?package=monthly">
                    Pilih Paket Bulanan
                </a>
            </article>

            <article class="plan-card best-plan">
                <span class="label-best">BEST VALUE</span>
                <h2>Paket Tahunan</h2>
                <h3>Rp 399.000 <span>thn</span></h3>
                <small>Hemat Rp 189.000 per tahun</small>
                <hr>
                <p>✓ Prioritas Matching Traveler</p>
                <p>✓ Diskon Fee Platform 100% (Bebas Biaya)</p>
                <p>✓ Layanan Bantuan VIP (Dipercepat)</p>

                <a class="btn yellow-btn" href="jastip_plus_payment.php?package=yearly">
                    Berlangganan Sekarang
                </a>
            </article>
        </div>
    </section>
</main>
</body>
</html>
