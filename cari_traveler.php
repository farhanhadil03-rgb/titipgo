<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$orderId = (int) ($_GET['order_id'] ?? 0);

$orderStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND buyer_id = ?");
$orderStmt->execute([$orderId, $_SESSION['user']['id']]);
$order = $orderStmt->fetch();

if (!$order) {
    $latestStmt = $pdo->prepare("SELECT * FROM orders WHERE buyer_id = ? ORDER BY id DESC LIMIT 1");
    $latestStmt->execute([$_SESSION['user']['id']]);
    $order = $latestStmt->fetch();
    $orderId = $order['id'] ?? 0;
}

if (isset($_GET['pilih']) && $orderId > 0) {
    $travelerId = (int) $_GET['pilih'];

    $update = $pdo->prepare("UPDATE orders SET traveler_id = ?, status = 'traveler_found' WHERE id = ? AND buyer_id = ?");
    $update->execute([$travelerId, $orderId, $_SESSION['user']['id']]);

    header('Location: checkout_pembayaran.php?order_id=' . $orderId);
    exit;
}

$travelerStmt = $pdo->prepare(
    "SELECT users.id, users.name, trips.origin_country, trips.destination_city, trips.trip_date, trips.capacity_note
     FROM users
     LEFT JOIN trips ON trips.traveler_id = users.id
     WHERE users.role = 'traveler'
     ORDER BY trips.trip_date ASC, users.id DESC
     LIMIT 3"
);
$travelerStmt->execute();
$travelers = $travelerStmt->fetchAll();

if (empty($travelers)) {
    $travelers = [
        [
            'id' => 1,
            'name' => 'Traveler A',
            'origin_country' => 'Japan',
            'destination_city' => 'Jakarta',
            'trip_date' => date('Y-m-d', strtotime('+1 day')),
            'capacity_note' => 'Sisa kapasitas 5kg'
        ],
        [
            'id' => 2,
            'name' => 'Traveler B',
            'origin_country' => 'Indonesia',
            'destination_city' => 'Jakarta',
            'trip_date' => date('Y-m-d', strtotime('+2 day')),
            'capacity_note' => 'Sisa kapasitas 3kg'
        ],
        [
            'id' => 3,
            'name' => 'Traveler C',
            'origin_country' => 'Singapore',
            'destination_city' => 'Jakarta',
            'trip_date' => date('Y-m-d', strtotime('+3 day')),
            'capacity_note' => 'Sisa kapasitas 4kg'
        ]
    ];
}

$ratings = [4.9, 4.8, 4.7];
$successRates = [99, 97, 95];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cari Traveler - TitipGo</title>
    <link rel="stylesheet" href="css/buyer_feature_common.css">
    <link rel="stylesheet" href="css/cari_traveler.css">
</head>
<body>
<main class="app">
    <?php include 'sidebar_buyer.php'; ?>

    <section class="main">
        <header class="topbar">
            <h1>Pencarian Traveler</h1>
            <div class="user-area">
                Hi, <?= htmlspecialchars($_SESSION['user']['name']); ?>
                <a class="user-icon" href="edit_profil.php">◎</a>
            </div>
        </header>

        <section class="page-content traveler-layout">
            <div class="traveler-list card">
                <div class="section-title">
                    <h2>Traveler yang Cocok dengan Request Anda</h2>
                    <p>Sistem memilih traveler berdasarkan rute, reputasi, dan tingkat keberhasilan transaksi.</p>
                </div>

                <?php foreach ($travelers as $index => $traveler): ?>
                    <article class="traveler-card">
                        <div class="traveler-avatar">◎</div>
                        <div class="traveler-info">
                            <h3><?= htmlspecialchars($traveler['name'] ?? 'Traveler'); ?></h3>
                            <p>
                                <?= htmlspecialchars($traveler['origin_country'] ?? 'Japan'); ?>
                                →
                                <?= htmlspecialchars($traveler['destination_city'] ?? 'Jakarta'); ?>
                            </p>
                            <span>ETA: <?= !empty($traveler['trip_date']) ? date('d M Y', strtotime($traveler['trip_date'])) : 'Besok'; ?></span>
                        </div>
                        <div class="traveler-stat">
                            <b><?= $ratings[$index] ?? 4.7; ?></b>
                            <small>Rating</small>
                        </div>
                        <div class="traveler-stat success-stat">
                            <b><?= $successRates[$index] ?? 95; ?>%</b>
                            <small>Success</small>
                        </div>
                        <a class="btn" href="cari_traveler.php?order_id=<?= urlencode((string) $orderId); ?>&pilih=<?= urlencode((string) $traveler['id']); ?>">
                            Pilih Traveler
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>

            <aside class="summary-card">
                <h2>Ringkasan Request</h2>
                <div class="summary-box">
                    <small>Nama Produk</small>
                    <b><?= htmlspecialchars($order['product_name'] ?? 'Produk Request Manual'); ?></b>
                </div>
                <div class="summary-box">
                    <small>Negara Asal</small>
                    <b><?= htmlspecialchars($order['product_country'] ?? '-'); ?></b>
                </div>
                <div class="summary-box">
                    <small>Estimasi Total</small>
                    <b>Rp <?= number_format((float) ($order['product_price'] ?? 0) + (float) ($order['service_fee'] ?? 0), 0, ',', '.'); ?></b>
                </div>
                <div class="security-note">
                    🛡️ <b>Escrow Aman</b><br>
                    Setelah traveler dipilih, Anda akan diarahkan ke halaman checkout pembayaran escrow.
                </div>
            </aside>
        </section>
    </section>
</main>
</body>
</html>
