<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE buyer_id = ? ORDER BY id DESC");
$stmt->execute([$_SESSION['user']['id']]);
$orders = $stmt->fetchAll();

if (empty($orders)) {
    $orders = [
        [
            'id' => 1,
            'product_name' => 'Kimono Yukata',
            'product_country' => 'Japan',
            'product_price' => 1525000,
            'service_fee' => 75000,
            'status' => 'completed'
        ],
        [
            'id' => 2,
            'product_name' => 'Bakpia Pathok 25 Premium Kacang Hijau',
            'product_country' => 'Indonesia',
            'product_price' => 90000,
            'service_fee' => 25000,
            'status' => 'completed'
        ],
        [
            'id' => 3,
            'product_name' => 'Kopi Bubuk Khas Gayo Premium 500g',
            'product_country' => 'Indonesia',
            'product_price' => 145000,
            'service_fee' => 30000,
            'status' => 'cancelled'
        ],
        [
            'id' => 4,
            'product_name' => 'Kain Tenun Sumba UMKM NTT',
            'product_country' => 'Indonesia',
            'product_price' => 525000,
            'service_fee' => 75000,
            'status' => 'on_delivery'
        ]
    ];
}

function statusLabel($status) {
    if ($status === 'completed') {
        return 'Selesai';
    }

    if ($status === 'cancelled') {
        return 'Refunded';
    }

    if ($status === 'on_delivery') {
        return 'Lacak Kurir';
    }

    if ($status === 'escrow_paid') {
        return 'Escrow Aktif';
    }

    if ($status === 'request_created') {
        return 'Menunggu Traveler';
    }

    return 'Diproses';
}

function statusClass($status) {
    if ($status === 'completed') {
        return 'done';
    }

    if ($status === 'cancelled') {
        return 'refund';
    }

    if ($status === 'on_delivery') {
        return 'track';
    }

    return 'process';
}

function totalPrice($order) {
    $productPrice = (float) ($order['product_price'] ?? 0);
    $serviceFee = (float) ($order['service_fee'] ?? 0);

    return $productPrice + $serviceFee;
}

function orderCountry($order) {
    return $order['product_country'] ?? '-';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan - TitipGo</title>
    <link rel="stylesheet" href="css/buyer_feature_common.css">
    <link rel="stylesheet" href="css/riwayat_pesanan.css">
</head>
<body>
<main class="app">
    <?php include 'sidebar_buyer.php'; ?>

    <section class="main">
        <header class="topbar">
            <h1>Riwayat Transaksi Jastip</h1>
            <div class="user-area">
                Hi, <?= htmlspecialchars($_SESSION['user']['name']); ?>
                <a class="user-icon" href="edit_profil.php">♙</a>
            </div>
        </header>

        <section class="page-content">
            <div class="history-header">
                <div>
                    <h2>Semua Pesanan Anda</h2>
                    <p>Kelola riwayat transaksi, tracking, invoice, repeat order, dan review dalam satu halaman.</p>
                </div>
                <a class="primary-action" href="buyer.php">+ Jelajahi Produk</a>
            </div>

            <div class="history-list">
                <?php foreach ($orders as $order): ?>
                    <?php
                        $orderId = (int) $order['id'];
                        $total = totalPrice($order);
                        $encodedProduct = urlencode($order['product_name']);
                        $encodedCountry = urlencode(orderCountry($order));
                        $encodedPrice = urlencode((string) ($order['product_price'] ?? 0));
                    ?>
                    <article class="history-card">
                        <div class="product-thumb">🎎</div>

                        <div class="history-info">
                            <h3><?= htmlspecialchars($order['product_name']); ?></h3>
                            <p>
                                Negara: <?= htmlspecialchars(orderCountry($order)); ?>
                                • ID Pesanan: #TG-<?= str_pad($orderId, 5, '0', STR_PAD_LEFT); ?>
                            </p>
                            <span class="status <?= statusClass($order['status']); ?>">
                                <?= statusLabel($order['status']); ?>
                            </span>
                        </div>

                        <div class="price-box">
                            <small>Total Pembayaran</small>
                            <strong>Rp <?= number_format($total, 0, ',', '.'); ?></strong>
                        </div>

                        <div class="action-grid">
                            <a class="history-btn detail-btn" href="detail_riwayat.php?id=<?= $orderId; ?>">
                                Detail
                            </a>

                            <a class="history-btn tracking-btn" href="qc_room.php?order_id=<?= $orderId; ?>">
                                Tracking
                            </a>

                            <a class="history-btn repeat-btn" href="request_manual.php?product=<?= $encodedProduct; ?>&country=<?= $encodedCountry; ?>&price=<?= $encodedPrice; ?>">
                                Repeat Order
                            </a>

                            <a
                                class="history-btn invoice-btn"
                                onclick="showInvoicePopup(event,this)"
                              >
                                Download Invoice
                            </a>

                            <a class="history-btn review-btn" href="review_layanan.php?order_id=<?= $orderId; ?>">
                                Review
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </section>
</main>

<div class="invoice-popup" id="invoicePopup">
    <div class="invoice-popup-box">
        <div class="popup-icon">✓</div>
        <h2>Invoice Berhasil Download</h2>
        <p>Invoice pesanan Anda berhasil diproses dan siap digunakan sebagai bukti transaksi.</p>

        <button type="button" onclick="closeInvoicePopup()">
            Oke
        </button>
    </div>
</div>

<script>
function showInvoicePopup(event, element)
{
    event.preventDefault();

    const popup = document.getElementById('invoicePopup');
    popup.classList.add('show');

    setTimeout(function()
    {
        window.location.href = element.href;
    }, 500);
}

function closeInvoicePopup()
{
    document.getElementById('invoicePopup').classList.remove('show');
}
</script>

</body>
</html>
