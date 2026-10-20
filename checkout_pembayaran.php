<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$orderId = $_GET['order_id'] ?? null;

if ($orderId) {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name AS traveler_name
        FROM orders o
        LEFT JOIN users u ON o.traveler_id = u.id
        WHERE o.id = ?
        AND o.buyer_id = ?
    ");
    $stmt->execute([$orderId, $_SESSION['user']['id']]);
} else {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name AS traveler_name
        FROM orders o
        LEFT JOIN users u ON o.traveler_id = u.id
        WHERE o.buyer_id = ?
        ORDER BY o.id DESC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user']['id']]);
}

$order = $stmt->fetch();

$product = $order['product_name'] ?? 'Kimono Yukata';
$country = $order['product_country'] ?? 'Japan';
$city = $order['product_city'] ?? 'Tokyo';
$traveler = $order['traveler_name'] ?? 'Andi Pratama';
$itemPrice = $order ? (float) $order['product_price'] : 1450000;
$serviceFee = $order ? (float) $order['service_fee'] : 75000;
$adminFee = $order ? (float) $order['admin_fee'] : 0;
$totalPrice = $order && !empty($order['total_price'])
    ? (float) $order['total_price']
    : $itemPrice + $serviceFee + $adminFee;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $order) {
    $deliveryMethod = $_POST['delivery_method'] ?? 'standard';

    $update = $pdo->prepare("
        UPDATE orders
        SET status = 'escrow_paid',
            delivery_option = ?
        WHERE id = ?
        AND buyer_id = ?
    ");

    $update->execute([
        $deliveryMethod,
        $order['id'],
        $_SESSION['user']['id']
    ]);

    if ($deliveryMethod === 'private_courier') {
        header('Location: kurir_domestik.php?order_id=' . $order['id']);
    } else {
        header('Location: qc_room.php?order_id=' . $order['id']);
    }

    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Pembayaran - TitipGo</title>
    <link rel="stylesheet" href="css/buyer_feature_common.css">
    <link rel="stylesheet" href="css/checkout_pembayaran.css">
</head>
<body>
<main class="app">
    <?php include 'sidebar_buyer.php'; ?>

    <section class="main">
        <header class="topbar">
            <h1>Selesaikan Pembayaran</h1>

            <div class="user-area">
                Hi, <?= htmlspecialchars($_SESSION['user']['name']); ?>
                <a class="user-icon" href="edit_profil_buyer.php">♙</a>
            </div>
        </header>

        <form class="page-content checkout-layout" method="POST">
            <section class="checkout-card card">
                <h2>Metode Pembayaran (Verifikasi Otomatis)</h2>

                <label class="choice active">
                    <input type="radio" name="payment_method" value="bca_va" checked>
                    <span>
                        <b>BCA Virtual Account</b>
                        <small>Biaya admin: Rp 0</small>
                    </span>
                </label>

                <label class="choice">
                    <input type="radio" name="payment_method" value="ewallet">
                    <span>
                        <b>GoPay / e-Wallet Lainnya</b>
                        <small>Biaya admin: Rp 1.500</small>
                    </span>
                </label>

                <hr>

                <h2>Opsi Metode Pengiriman Barang</h2>

                <label class="choice active">
                    <input type="radio" name="delivery_method" value="standard" checked>
                    <span>
                        <b>1. Ketemu Jastiper (Meetup / Hand-Carry)</b>
                        <small>Biaya penanganan: Rp 0 • Cocok untuk barang rapuh/makanan segar</small>
                    </span>
                </label>

                <label class="choice">
                    <input type="radio" name="delivery_method" value="private_courier">
                    <span>
                        <b>2. Oleh Kurir Domestik (Kirim ke Alamat)</b>
                        <small>Dikirim lewat logistik rekanan setelah jastiper mendarat di kota tujuan</small>
                    </span>
                </label>
            </section>

            <aside class="summary-card card">
                <h2>Rincian Titipan</h2>

                <div class="summary-box">
                    <b><?= htmlspecialchars($product); ?></b>

                    <p>Negara/Kota Asal: <?= htmlspecialchars($country); ?>, <?= htmlspecialchars($city); ?></p>
                    <p>Traveler: <?= htmlspecialchars($traveler); ?> (ETA: Besok)</p>

                    <div class="summary-row">
                        <span>Harga Barang</span>
                        <strong>Rp <?= number_format($itemPrice, 0, ',', '.'); ?></strong>
                    </div>

                    <div class="summary-row">
                        <span>Fee Traveler</span>
                        <strong>Rp <?= number_format($serviceFee, 0, ',', '.'); ?></strong>
                    </div>

                    <div class="summary-row">
                        <span>Biaya Admin</span>
                        <strong>Rp <?= number_format($adminFee, 0, ',', '.'); ?></strong>
                    </div>

                    <div class="summary-total">
                        <span>Total Tagihan</span>
                        <strong>Rp <?= number_format($totalPrice, 0, ',', '.'); ?></strong>
                    </div>
                </div>

                <div class="protection">
                    🛡️ <b>100% Perlindungan Escrow</b><br>
                    Sistem menahan uangmu dengan aman. Dana hanya dicairkan setelah kamu menerima barang dalam kondisi baik.
                </div>

                <button class="btn" type="submit">
                    Bayar Rp <?= number_format($totalPrice, 0, ',', '.'); ?>
                </button>
            </aside>
        </form>
    </section>
</main>

<script>
document.querySelectorAll('.choice').forEach(function(choice) {
    choice.addEventListener('click', function() {
        var input = choice.querySelector('input');
        var name = input.name;

        document.querySelectorAll('input[name="' + name + '"]').forEach(function(item) {
            item.closest('.choice').classList.remove('active');
        });

        choice.classList.add('active');
        input.checked = true;
    });
});
</script>
</body>
</html>
