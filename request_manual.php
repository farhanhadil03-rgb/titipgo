<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$productName = $_GET['product'] ?? '';
$productCountry = $_GET['country'] ?? '';
$productCity = $_GET['city'] ?? '';
$productPrice = $_GET['price'] ?? 450000;
$error = '';

$countries = $pdo->query("SELECT DISTINCT country FROM country_cities ORDER BY country ASC")->fetchAll(PDO::FETCH_COLUMN);

if (empty($countries)) {
    $countries = $pdo->query("SELECT DISTINCT country FROM products ORDER BY country ASC")->fetchAll(PDO::FETCH_COLUMN);
}

$selectedCountry = $_POST['product_country'] ?? $productCountry;
$cities = [];

if ($selectedCountry !== '') {
    $cityStmt = $pdo->prepare("
        SELECT city
        FROM country_cities
        WHERE country = ?
        ORDER BY active_travelers DESC, city ASC
    ");
    $cityStmt->execute([$selectedCountry]);
    $cities = $cityStmt->fetchAll(PDO::FETCH_COLUMN);
}

if (empty($cities)) {
    $cities = ['Tokyo', 'Osaka', 'Kyoto', 'Yogyakarta', 'Bali', 'Singapore', 'Bandung'];
}

function getDefaultTravelerId($pdo)
{
    $stmt = $pdo->prepare("
        SELECT id
        FROM users
        WHERE role = 'traveler'
        ORDER BY id ASC
        LIMIT 1
    ");
    $stmt->execute();
    $travelerId = $stmt->fetchColumn();

    if (!$travelerId) {
        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            ORDER BY id ASC
            LIMIT 1
        ");
        $stmt->execute();
        $travelerId = $stmt->fetchColumn();
    }

    return $travelerId;
}

function getDefaultProductId($pdo, $productName)
{
    if ($productName !== '') {
        $stmt = $pdo->prepare("
            SELECT id
            FROM products
            WHERE name = ?
            LIMIT 1
        ");
        $stmt->execute([$productName]);
        $productId = $stmt->fetchColumn();

        if ($productId) {
            return $productId;
        }
    }

    $stmt = $pdo->prepare("
        SELECT id
        FROM products
        ORDER BY id ASC
        LIMIT 1
    ");
    $stmt->execute();

    return $stmt->fetchColumn() ?: 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $handlingFee = ($_POST['handling'] ?? 'fragile') === 'fragile' ? 0 : 15000;
    $serviceFee = 25000 + $handlingFee;
    $adminFee = 0;

    $city = $_POST['city'] ?? '';
    $note = trim($_POST['note'] ?? '');
    $referenceName = '';

    if (!empty($_FILES['reference_image']['name'])) {
        $referenceName = $_FILES['reference_image']['name'];
    }

    $fullNote = $note !== '' ? $note : 'Request manual dari buyer.';
    $fullNote .= "\nKota asal: " . ($city !== '' ? $city : 'Belum dipilih');

    if ($referenceName !== '') {
        $fullNote .= "\nFoto referensi: " . $referenceName;
    }

    $travelerId = getDefaultTravelerId($pdo);
    $productId = getDefaultProductId($pdo, $_POST['product_name'] ?? '');
    $itemPrice = (float) ($_POST['product_price'] ?? 0);
    $totalPrice = $itemPrice + $serviceFee + $adminFee;

    if (!$travelerId) {
        $error = 'Belum ada user dengan role traveler di database. Tambahkan minimal 1 traveler dulu.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO orders
            (
                buyer_id,
                traveler_id,
                product_id,
                product_name,
                product_country,
                product_city,
                product_price,
                service_fee,
                admin_fee,
                total_price,
                delivery_option,
                status,
                note
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                'request_created',
                ?
            )
        ");

        $stmt->execute([
            $_SESSION['user']['id'],
            $travelerId,
            $productId,
            $_POST['product_name'],
            $_POST['product_country'],
            $city !== '' ? $city : 'Bandung',
            $itemPrice,
            $serviceFee,
            $adminFee,
            $totalPrice,
            $_POST['delivery_option'],
            $fullNote
        ]);

        header('Location: checkout_pembayaran.php?order_id=' . $pdo->lastInsertId());
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Manual - TitipGo</title>
    <link rel="stylesheet" href="css/buyer_feature_common.css">
    <link rel="stylesheet" href="css/request_manual.css">
</head>
<body>
<main class="app">
    <?php include 'sidebar_buyer.php'; ?>

    <section class="main">
        <header class="topbar">
            <h1>Buat Permintaan Khusus</h1>
            <div class="user-area">
                Hi, <?= htmlspecialchars($_SESSION['user']['name']); ?>
                <a class="user-icon" href="edit_profil_buyer.php">◎</a>
            </div>
        </header>

        <section class="page-content request-layout">
            <form class="detail-card card" method="POST" enctype="multipart/form-data">
                <h2>Detail Produk</h2>

                <?php if ($error): ?>
                    <div class="alert error-alert"><?= htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <label>Nama Produk</label>
                <input type="text" name="product_name" value="<?= htmlspecialchars($productName); ?>" placeholder="Contoh: Kimono Yukata" required>

                <label>Negara Asal</label>
                <select name="product_country" required>
                    <option value="">Pilih negara asal produk</option>
                    <?php foreach ($countries as $country): ?>
                        <option value="<?= htmlspecialchars($country); ?>" <?= $selectedCountry === $country ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($country); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Kota Asal Produk</label>
                <select name="city" required>
                    <option value="">Pilih kota asal produk</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?= htmlspecialchars($city); ?>" <?= $productCity === $city ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($city); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Harga Barang</label>
                <input type="number" name="product_price" value="<?= htmlspecialchars($productPrice); ?>" required>

                <label>Deskripsi Spesifik</label>
                <textarea name="note" placeholder='Contoh: "Ramen cup, berbagai varian, 2 pcs."'></textarea>

                <label>Foto Referensi Produk</label>
                <input class="file-input" type="file" name="reference_image" accept="image/*">

                <h3>Opsi Keamanan Pengiriman</h3>

                <label class="option-box red-option">
                    <input type="radio" name="handling" value="standard">
                    <span>
                        <b>Standard Handling (Masuk Bagasi)</b>
                        <small>Untuk baju atau barang tidak mudah pecah.</small>
                    </span>
                    <strong>+ Rp 15.000</strong>
                </label>

                <label class="option-box green-option">
                    <input type="radio" name="handling" value="fragile" checked>
                    <span>
                        <b>Fragile Mode (Wajib Masuk Kabin Pesawat)</b>
                        <small>Aman untuk makanan dan produk rapuh.</small>
                    </span>
                </label>

                <input type="hidden" name="delivery_option" value="standard">
                <input type="hidden" name="submit_request" value="1">
            </form>

            <aside class="payment-card">
                <h2>Rincian Pembayaran</h2>

                <div class="pay-row">
                    <span>Harga Barang</span>
                    <b>Rp <?= number_format((float) $productPrice, 0, ',', '.'); ?></b>
                </div>

                <div class="pay-row">
                    <span>Fee Traveler (Dinamis)</span>
                    <b>Rp 25.000</b>
                </div>

                <div class="pay-row green-text">
                    <span>Estimasi Total Escrow</span>
                    <b>Rp <?= number_format((float) $productPrice + 25000, 0, ',', '.'); ?></b>
                </div>

                <hr>

                <p>Total Tagihan Sementara</p>
                <h3>Rp <?= number_format((float) $productPrice + 25000, 0, ',', '.'); ?></h3>

                <button class="btn" type="button" onclick="document.querySelector('.detail-card').submit()">
                    Cari Traveler
                </button>

                <div class="guarantee">
                    🛡️ <b>Platform Guarantee</b><br>
                    Setelah request dikirim, sistem akan mencarikan traveler yang sesuai rute dan reputasinya.
                </div>
            </aside>
        </section>
    </section>
</main>
</body>
</html>
