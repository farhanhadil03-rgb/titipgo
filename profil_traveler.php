<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$travelerId = isset($_GET['traveler_id']) ? (int) $_GET['traveler_id'] : 0;

if ($travelerId > 0) {
    $travelerStmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'traveler' LIMIT 1");
    $travelerStmt->execute([$travelerId]);
} else {
    $travelerStmt = $pdo->query("SELECT * FROM users WHERE role = 'traveler' ORDER BY id DESC LIMIT 1");
}

$traveler = $travelerStmt->fetch();

if (!$traveler) {
    $traveler = [
        'id' => 0,
        'name' => 'Traveler Belum Tersedia',
        'email' => '-',
        'phone' => '-'
    ];
}

$travelerId = (int) $traveler['id'];

$ratingStmt = $pdo->prepare(
    "SELECT 
        COALESCE(AVG(rating), 0) AS average_rating,
        COUNT(*) AS total_reviews
     FROM traveler_reviews
     WHERE traveler_id = ?"
);
$ratingStmt->execute([$travelerId]);
$ratingData = $ratingStmt->fetch();

$averageRating = (float) ($ratingData['average_rating'] ?? 0);
$totalReviews = (int) ($ratingData['total_reviews'] ?? 0);

$orderStatsStmt = $pdo->prepare(
    "SELECT
        COUNT(*) AS total_orders,
        SUM(CASE WHEN status IN ('completed', 'selesai') THEN 1 ELSE 0 END) AS completed_orders,
        SUM(CASE WHEN status IN ('cancelled', 'refunded') THEN 1 ELSE 0 END) AS failed_orders
     FROM orders
     WHERE traveler_id = ?"
);
$orderStatsStmt->execute([$travelerId]);
$orderStats = $orderStatsStmt->fetch();

$totalOrders = (int) ($orderStats['total_orders'] ?? 0);
$completedOrders = (int) ($orderStats['completed_orders'] ?? 0);
$failedOrders = (int) ($orderStats['failed_orders'] ?? 0);
$successRate = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 1) : 0;

$reviewStmt = $pdo->prepare(
    "SELECT traveler_reviews.*, users.name AS buyer_name
     FROM traveler_reviews
     LEFT JOIN users ON users.id = traveler_reviews.buyer_id
     WHERE traveler_reviews.traveler_id = ?
     ORDER BY traveler_reviews.created_at DESC
     LIMIT 3"
);
$reviewStmt->execute([$travelerId]);
$reviews = $reviewStmt->fetchAll();

$recentOrderStmt = $pdo->prepare(
    "SELECT product_name, product_country, status, product_price
     FROM orders
     WHERE traveler_id = ?
     ORDER BY id DESC
     LIMIT 4"
);
$recentOrderStmt->execute([$travelerId]);
$recentOrders = $recentOrderStmt->fetchAll();

function renderStars($rating) {
    $roundedRating = (int) round((float) $rating);
    $stars = '';

    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= $roundedRating ? '★' : '☆';
    }

    return $stars;
}

function statusText($status) {
    if ($status === 'completed' || $status === 'selesai') {
        return 'Selesai';
    }

    if ($status === 'cancelled' || $status === 'refunded') {
        return 'Gagal / Refund';
    }

    if ($status === 'on_delivery') {
        return 'Dalam Pengiriman';
    }

    if ($status === 'traveler_found') {
        return 'Traveler Dipilih';
    }

    return 'Diproses';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Traveler - TitipGo</title>
    <link rel="stylesheet" href="css/buyer_feature_common.css">
    <link rel="stylesheet" href="css/profil_traveler.css">
</head>
<body>
<main class="app">
    <?php include 'sidebar_buyer.php'; ?>

    <section class="main">
        <header class="topbar">
            <h1>Reputasi Publik Traveler</h1>
            <div class="user-area">
                Hi, <?= htmlspecialchars($_SESSION['user']['name']); ?>
                <a class="user-icon" href="edit_profil.php">◎</a>
            </div>
        </header>

        <section class="page-content profile-layout">
            <div class="profile-card card">
                <div class="profile-head">
                    <div class="big-avatar">◎</div>
                    <div>
                        <h2><?= htmlspecialchars($traveler['name']); ?></h2>
                        <span class="verified">✓ Verified Identity (KTP)</span>
                        <p class="profile-subtitle">
                            Traveler aktif dengan performa berdasarkan data order dan ulasan buyer.
                        </p>
                    </div>
                </div>

                <div class="quick-stats">
                    <div class="quick-box">
                        <span>Rating</span>
                        <b><?= $averageRating > 0 ? number_format($averageRating, 1) : '0.0'; ?></b>
                        <small><?= renderStars($averageRating); ?></small>
                    </div>
                    <div class="quick-box">
                        <span>Transaksi</span>
                        <b><?= $completedOrders; ?>x</b>
                        <small>Selesai</small>
                    </div>
                    <div class="quick-box">
                        <span>Success Rate</span>
                        <b><?= number_format($successRate, 1); ?>%</b>
                        <small><?= $failedOrders; ?> kendala tercatat</small>
                    </div>
                </div>

                <h3>Badge Keahlian Berdasarkan Performa</h3>
                <div class="badge-grid">
                    <div class="skill orange">
                        🍜 <b>Spesialis Produk Lokal</b><br>
                        <small><?= $completedOrders; ?>x transaksi berhasil</small>
                    </div>
                    <div class="skill green">
                        📦 <b>Aman Bawa Barang</b><br>
                        <small><?= number_format($successRate, 1); ?>% success rate</small>
                    </div>
                </div>

                <h3>Testimoni Asli Pembeli</h3>
                <?php if (empty($reviews)): ?>
                    <div class="testimonial empty-review">
                        Belum ada ulasan untuk traveler ini. Ulasan akan muncul setelah buyer menyelesaikan pesanan dan memberi rating.
                    </div>
                <?php endif; ?>

                <?php foreach ($reviews as $review): ?>
                    <div class="testimonial">
                        <div class="review-head">
                            <b><?= renderStars($review['rating']); ?></b>
                            <span><?= htmlspecialchars($review['buyer_name'] ?? 'Buyer'); ?></span>
                        </div>
                        <p>"<?= htmlspecialchars($review['review']); ?>"</p>
                    </div>
                <?php endforeach; ?>
            </div>

            <aside class="stats-card">
                <h2>Statistik Traveler</h2>

                <p>Rating Rata-rata</p>
                <h3><?= $averageRating > 0 ? number_format($averageRating, 1) : '0.0'; ?></h3>
                <span class="side-note"><?= $totalReviews; ?> ulasan dari buyer</span>

                <hr>

                <p>Tingkat Kesuksesan Order</p>
                <h3><?= number_format($successRate, 1); ?>%</h3>
                <span class="side-note"><?= $completedOrders; ?> dari <?= $totalOrders; ?> transaksi selesai</span>

                <hr>

                <p>Total Transaksi</p>
                <h4><?= $totalOrders; ?>x Titipan</h4>

                <hr>

                <a class="btn" href="request_manual.php?traveler_id=<?= $travelerId; ?>">Request ke Traveler Ini</a>
            </aside>
        </section>

        <section class="page-content order-section">
            <div class="card recent-card">
                <h2>Riwayat Order Traveler</h2>
                <?php if (empty($recentOrders)): ?>
                    <p class="muted-text">Belum ada transaksi yang terhubung dengan traveler ini.</p>
                <?php endif; ?>

                <?php foreach ($recentOrders as $order): ?>
                    <div class="order-row">
                        <div>
                            <b><?= htmlspecialchars($order['product_name']); ?></b>
                            <p><?= htmlspecialchars($order['product_country']); ?> • Rp <?= number_format((float) $order['product_price'], 0, ',', '.'); ?></p>
                        </div>
                        <span><?= statusText($order['status']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </section>
</main>
</body>
</html>
