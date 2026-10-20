<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$productStmt = $pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$productStmt->execute([$productId]);
$product = $productStmt->fetch();

if (!$product) {
    header('Location: buyer.php');
    exit;
}

$travelerStmt = $pdo->prepare(
    "SELECT users.id, users.name, trips.origin_country, trips.destination_city, trips.trip_date, trips.capacity_note
     FROM trips
     JOIN users ON users.id = trips.traveler_id
     WHERE users.role = 'traveler'
       AND trips.origin_country = ?
       AND trips.trip_date >= CURDATE()
     ORDER BY trips.trip_date ASC
     LIMIT 1"
);
$travelerStmt->execute([$product['country']]);
$traveler = $travelerStmt->fetch();

if (!$traveler) {
    $traveler = [
        'id' => 0,
        'name' => 'Andi Pratama',
        'origin_country' => $product['country'],
        'destination_city' => 'Jakarta (CGK)',
        'trip_date' => date('Y-m-d', strtotime('+2 days')),
        'capacity_note' => 'Sisa kapasitas 5kg'
    ];
}

$price = (float) $product['price'];
$travelerFee = max(25000, round($price * 0.05));
$adminFee = round($price * 0.05);
$total = $price + $travelerFee + $adminFee;
$eta = date('d M Y', strtotime($traveler['trip_date']));
$rating = 4.9;
$successRate = 99.3;
$totalCompleted = 1248;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Produk - TitipGo</title>
    <link rel="stylesheet" href="css/detail_produk.css">
</head>
<body>
<main class="app">
    <aside class="sidebar">
        <div class="brand">TitipGo</div>
        <nav class="menu">
            <a class="active" href="buyer.php">🛍️ Discovery Produk</a>
            <a href="request_manual.php">🚕 Request Manual</a>
            <a href="qc_room.php">📦 Lacak & QC Room</a>
            <a href="checkout_pembayaran.php">💳 Checkout Pembayaran</a>
            <a href="profil_traveler.php">👤 Profil Traveler</a>
            <a href="riwayat_pesanan.php">📜 Riwayat Pesanan</a>
            <a href="jastip_plus.php">🔒 Jastip Plus Premium</a>
            <a href="edit_profil.php">⚙️ Edit Profil & Sandi</a>
            <a href="dashboard.php">🔁 Pilih Peran</a>
            <a href="logout.php">🚪 Logout</a>
        </nav>
        <div class="plus"><b>Jastip Plus 🔒</b><br>Bebas fee & prioritas matching</div>
    </aside>

    <section class="main">
        <header class="topbar">
            <h1>Detail Produk</h1>
            <div class="user-area">Hi, <?= htmlspecialchars($_SESSION['user']['name']); ?> <a class="user-icon" href="edit_profil.php">◎</a></div>
        </header>

        <section class="detail-layout">
            <article class="product-detail card">
                <a class="back-link" href="buyer.php">← Kembali ke Discovery Produk</a>
                <div class="product-head">
                    <div class="product-photo">📦</div>
                    <div>
                        <span class="tag">Produk Pilihan TitipGo</span>
                        <h2><?= htmlspecialchars($product['name']); ?></h2>
                        <p><?= htmlspecialchars($product['description'] ?? 'Produk titipan dari traveler terpercaya.'); ?></p>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-box">
                        <span>Harga Produk</span>
                        <b>Rp <?= number_format($price, 0, ',', '.'); ?></b>
                    </div>
                    <div class="info-box">
                        <span>Fee Traveler</span>
                        <b>Rp <?= number_format($travelerFee, 0, ',', '.'); ?></b>
                    </div>
                    <div class="info-box">
                        <span>Negara Asal</span>
                        <b><?= htmlspecialchars($product['country']); ?></b>
                    </div>
                    <div class="info-box">
                        <span>Kota Asal</span>
                        <b><?= htmlspecialchars($product['city'] ?? '-'); ?></b>
                    </div>
                    <div class="info-box">
                        <span>Estimasi Tiba</span>
                        <b><?= htmlspecialchars($eta); ?></b>
                    </div>
                    <div class="info-box">
                        <span>Admin Fee User Biasa</span>
                        <b>Rp <?= number_format($adminFee, 0, ',', '.'); ?></b>
                    </div>
                </div>

                <div class="description-box">
                    <h3>Deskripsi Produk</h3>
                    <p>
                        Produk ini dapat dipesan melalui sistem TitipGo dengan perlindungan escrow.
                        Dana buyer akan ditahan terlebih dahulu sampai barang diterima dalam kondisi baik.
                        Buyer juga dapat memantau proses melalui QC Room dan tracking pesanan.
                    </p>
                </div>
            </article>

            <aside class="side-panel">
                <div class="traveler-card card">
                    <h3>Profil Traveler</h3>
                    <div class="traveler-profile">
                        <div class="avatar-large">◎</div>
                        <div>
                            <h4><?= htmlspecialchars($traveler['name']); ?></h4>
                            <span>✓ Verified Identity (KYC)</span>
                        </div>
                    </div>
                    <div class="rating-row">
                        <div>
                            <small>Rating Traveler</small>
                            <b>⭐ <?= number_format($rating, 1); ?>/5</b>
                        </div>
                        <div>
                            <small>Success Rate</small>
                            <b><?= number_format($successRate, 1); ?>%</b>
                        </div>
                    </div>
                    <div class="mini-stat">
                        <span>Total Transaksi Selesai</span>
                        <b><?= number_format($totalCompleted, 0, ',', '.'); ?>x Titipan Sukses</b>
                    </div>
                    <div class="route-box">
                        <b>Rute Traveler</b><br>
                        <?= htmlspecialchars($traveler['origin_country']); ?> → <?= htmlspecialchars($traveler['destination_city']); ?><br>
                        <small><?= htmlspecialchars($traveler['capacity_note']); ?></small>
                    </div>
                </div>

                <div class="checkout-card card">
                    <h3>Ringkasan Pesanan</h3>
                    <div class="cost-row"><span>Harga Barang</span><b>Rp <?= number_format($price, 0, ',', '.'); ?></b></div>
                    <div class="cost-row"><span>Fee Traveler</span><b>Rp <?= number_format($travelerFee, 0, ',', '.'); ?></b></div>
                    <div class="cost-row"><span>Admin Fee</span><b>Rp <?= number_format($adminFee, 0, ',', '.'); ?></b></div>
                    <hr>
                    <div class="total-row"><span>Total Escrow</span><b>Rp <?= number_format($total, 0, ',', '.'); ?></b></div>
                    <a class="btn" href="request_manual.php?product=<?= urlencode($product['name']); ?>&country=<?= urlencode($product['country']); ?>&price=<?= urlencode((string) $price); ?>">Pesan Sekarang</a>
                    <div class="guarantee">🛡️ Dana aman ditahan sistem escrow sampai barang diterima buyer.</div>
                </div>
            </aside>
        </section>
    </section>
</main>
</body>
</html>
