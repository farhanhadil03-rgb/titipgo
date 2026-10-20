<?php
require_once 'includes/traveler_auth.php';

$travelerId = $user['id'];
$errorInfo = '';
$successInfo = '';

function tableExists($pdo, $table)
{
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
        ");
        $stmt->execute([$table]);

        return (int) $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function columnExists($pdo, $table, $column)
{
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);

        return (int) $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function uploadProductPhoto($file)
{
    if (empty($file['name'])) {
        return null;
    }

    $uploadDir = __DIR__ . '/uploads/products/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions)) {
        return false;
    }

    $fileName = 'local_product_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'uploads/products/' . $fileName;
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productName = trim($_POST['product_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $commission = (float) ($_POST['commission'] ?? 0);
    $eta = trim($_POST['eta'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $labelTag = trim($_POST['label_tag'] ?? '');

    $photoPath = uploadProductPhoto($_FILES['product_photo'] ?? []);

    if ($photoPath === false) {
        $errorInfo = 'Format foto harus JPG, JPEG, PNG, atau WEBP.';
    } elseif ($productName === '' || $country === '' || $city === '' || $price <= 0) {
        $errorInfo = 'Nama produk, negara, kota, dan harga wajib diisi.';
    } else {
        try {
            $pdo->beginTransaction();

            $productColumns = ['name', 'description', 'country', 'city', 'price'];
            $productValues = [$productName, $description, $country, $city, $price];

            if (columnExists($pdo, 'products', 'traveler_id')) {
                $productColumns[] = 'traveler_id';
                $productValues[] = $travelerId;
            }

            if (columnExists($pdo, 'products', 'commission')) {
                $productColumns[] = 'commission';
                $productValues[] = $commission;
            }

            if (columnExists($pdo, 'products', 'eta')) {
                $productColumns[] = 'eta';
                $productValues[] = $eta;
            }

            if (columnExists($pdo, 'products', 'category')) {
                $productColumns[] = 'category';
                $productValues[] = $category;
            }

            if (columnExists($pdo, 'products', 'image')) {
                $productColumns[] = 'image';
                $productValues[] = $photoPath;
            } elseif (columnExists($pdo, 'products', 'image_path')) {
                $productColumns[] = 'image_path';
                $productValues[] = $photoPath;
            }

            if (columnExists($pdo, 'products', 'status')) {
                $productColumns[] = 'status';
                $productValues[] = 'active';
            }

            $placeholders = implode(', ', array_fill(0, count($productColumns), '?'));
            $sql = "
                INSERT INTO products
                (
                    " . implode(', ', $productColumns) . "
                )
                VALUES
                (
                    $placeholders
                )
            ";

            $insertProduct = $pdo->prepare($sql);
            $insertProduct->execute($productValues);
            $productId = $pdo->lastInsertId();

            if (tableExists($pdo, 'local_broadcasts')) {
                $insertBroadcast = $pdo->prepare("
                    INSERT INTO local_broadcasts
                    (
                        traveler_id,
                        product_id,
                        product_name,
                        country,
                        city,
                        price,
                        commission,
                        eta,
                        label_tag,
                        image_path,
                        status,
                        created_at
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
                        'active',
                        NOW()
                    )
                ");

                $insertBroadcast->execute([
                    $travelerId,
                    $productId,
                    $productName,
                    $country,
                    $city,
                    $price,
                    $commission,
                    $eta,
                    $labelTag,
                    $photoPath
                ]);
            }

            $pdo->commit();

            $successInfo = 'Produk lokal berhasil disiarkan dan akan muncul di Discovery Produk buyer.';
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errorInfo = 'Gagal menyimpan produk. Pastikan database sudah memiliki tabel products.';
        }
    }
}

$broadcasts = [];

try {
    if (tableExists($pdo, 'local_broadcasts')) {
        $stmt = $pdo->prepare("
            SELECT *
            FROM local_broadcasts
            WHERE traveler_id = ?
            ORDER BY id DESC
            LIMIT 6
        ");
        $stmt->execute([$travelerId]);
        $broadcasts = $stmt->fetchAll();
    } elseif (columnExists($pdo, 'products', 'traveler_id')) {
        $stmt = $pdo->prepare("
            SELECT *
            FROM products
            WHERE traveler_id = ?
            ORDER BY id DESC
            LIMIT 6
        ");
        $stmt->execute([$travelerId]);
        $broadcasts = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $broadcasts = [];
}

if (empty($broadcasts)) {
    $broadcasts = [
        [
            'product_name' => 'Snack Jepang Matcha Premium',
            'name' => 'Snack Jepang Matcha Premium',
            'country' => 'Japan',
            'city' => 'Tokyo',
            'price' => 85000,
            'commission' => 20000,
            'eta' => '3 hari',
            'label_tag' => 'Langka',
            'status' => 'active'
        ],
        [
            'product_name' => 'Merchandise Anime Limited',
            'name' => 'Merchandise Anime Limited',
            'country' => 'Japan',
            'city' => 'Osaka',
            'price' => 180000,
            'commission' => 35000,
            'eta' => '5 hari',
            'label_tag' => 'Limited',
            'status' => 'active'
        ],
        [
            'product_name' => 'Kosmetik Korea Travel Size',
            'name' => 'Kosmetik Korea Travel Size',
            'country' => 'South Korea',
            'city' => 'Seoul',
            'price' => 125000,
            'commission' => 25000,
            'eta' => '4 hari',
            'label_tag' => 'Trending',
            'status' => 'active'
        ]
    ];
}

$popularExamples = [
    'Snack Jepang',
    'Merchandise anime',
    'Produk UMKM Thailand',
    'Kosmetik Korea'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Finder - TitipGo</title>
    <link rel="stylesheet" href="css/local_finder.css">
</head>
<body>
<main class="app">
    <?php include 'includes/traveler_sidebar.php'; ?>

    <section class="main">
        <header class="topbar">
            <h1>Local Finder</h1>
            <a class="user-link" href="edit_profil.php">
                Hi! Jastiper
                <span class="user-icon">◎</span>
            </a>
        </header>

        <section class="page-content finder-layout">
            <section class="card broadcast-form-card">
                <p class="page-label">BROADCAST PRODUK LOKAL</p>
                <h2>Jadi Mata dan Tangan Buyer di Lokasimu</h2>
                <p class="intro-text">
                    Unggah produk unik yang kamu temukan di daerah atau negara tertentu agar muncul di Discovery Produk buyer.
                </p>

                <?php if ($successInfo): ?>
                    <div class="notice-green"><?= htmlspecialchars($successInfo); ?></div>
                <?php endif; ?>

                <?php if ($errorInfo): ?>
                    <div class="notice-red"><?= htmlspecialchars($errorInfo); ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <label class="upload-box">
                        <input type="file" name="product_photo" accept="image/*">
                        <div>
                            <div class="upload-icon">📷</div>
                            <b>Upload Foto Produk</b>
                            <p>Jepret barang unik, diskon lokal, atau produk khas daerah di lokasimu.</p>
                        </div>
                    </label>

                    <label>Nama Produk</label>
                    <input class="input" name="product_name" placeholder="Contoh: Snack Jepang Matcha Premium" required>

                    <label>Deskripsi Produk</label>
                    <textarea class="textarea" name="description" placeholder="Jelaskan kondisi produk, ukuran, varian, atau alasan produk ini menarik."></textarea>

                    <div class="form-grid">
                        <div>
                            <label>Negara Produk</label>
                            <select class="select" name="country" required>
                                <option value="">Pilih Negara</option>
                                <option value="Japan">Japan</option>
                                <option value="Indonesia">Indonesia</option>
                                <option value="Thailand">Thailand</option>
                                <option value="South Korea">South Korea</option>
                                <option value="Singapore">Singapore</option>
                            </select>
                        </div>

                        <div>
                            <label>Kota / Lokasi Produk</label>
                            <input class="input" name="city" placeholder="Contoh: Tokyo" required>
                        </div>

                        <div>
                            <label>Harga Produk</label>
                            <input class="input" type="number" name="price" placeholder="85000" required>
                        </div>

                        <div>
                            <label>Komisi Jastip</label>
                            <input class="input" type="number" name="commission" placeholder="20000">
                        </div>

                        <div>
                            <label>Estimasi Pengiriman</label>
                            <input class="input" name="eta" placeholder="Contoh: 3-5 hari">
                        </div>

                        <div>
                            <label>Kategori Produk</label>
                            <select class="select" name="category">
                                <option value="Makanan">Makanan / Snack</option>
                                <option value="Merchandise">Merchandise</option>
                                <option value="UMKM">Produk UMKM</option>
                                <option value="Kosmetik">Kosmetik</option>
                                <option value="Fashion">Fashion</option>
                            </select>
                        </div>
                    </div>

                    <h3 class="label-title">Label Daya Tarik</h3>
                    <div class="tag-options">
                        <label>
                            <input type="radio" name="label_tag" value="Langka" checked>
                            💎 Langka
                        </label>

                        <label>
                            <input type="radio" name="label_tag" value="Diskon Lokal">
                            🏷️ Diskon Lokal
                        </label>

                        <label>
                            <input type="radio" name="label_tag" value="Trending">
                            🔥 Trending
                        </label>
                    </div>

                    <button class="btn btn-full" type="submit">
                        Siarkan ke Pembeli
                    </button>
                </form>
            </section>

            <aside class="side-panel">
                <section class="card info-card">
                    <h2>Menampilkan Produk Lokal</h2>
                    <p>Contoh produk yang bisa disiarkan traveler:</p>

                    <div class="example-list">
                        <?php foreach ($popularExamples as $example): ?>
                            <span><?= htmlspecialchars($example); ?></span>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="card broadcast-card-wrapper">
                    <h2 class="section-title">Siaran Aktif Kamu</h2>

                    <div class="broadcast-list">
                        <?php foreach ($broadcasts as $broadcast): ?>
                            <?php
                                $broadcastName = $broadcast['product_name'] ?? $broadcast['name'] ?? 'Produk Lokal';
                                $broadcastCountry = $broadcast['country'] ?? '-';
                                $broadcastCity = $broadcast['city'] ?? '-';
                                $broadcastPrice = $broadcast['price'] ?? 0;
                                $broadcastCommission = $broadcast['commission'] ?? 0;
                                $broadcastEta = $broadcast['eta'] ?? 'Estimasi menyesuaikan rute';
                                $broadcastLabel = $broadcast['label_tag'] ?? 'Aktif';
                            ?>

                            <article class="broadcast-card">
                                <div class="broadcast-top">
                                    <h3><?= htmlspecialchars($broadcastName); ?></h3>
                                    <span><?= htmlspecialchars($broadcastLabel); ?></span>
                                </div>

                                <p class="muted">
                                    📍 <?= htmlspecialchars($broadcastCity); ?>,
                                    <?= htmlspecialchars($broadcastCountry); ?>
                                </p>

                                <div class="broadcast-meta">
                                    <b>Rp <?= number_format((float) $broadcastPrice, 0, ',', '.'); ?></b>
                                    <strong>+ Rp <?= number_format((float) $broadcastCommission, 0, ',', '.'); ?></strong>
                                </div>

                                <small>Estimasi kirim: <?= htmlspecialchars($broadcastEta); ?></small>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="card benefit-card">
                    <h2>Menjangkau Buyer Baru</h2>
                    <p>
                        Produk yang kamu broadcast akan masuk ke Discovery Produk buyer sehingga peluang order semakin besar.
                    </p>
                </section>
            </aside>
        </section>
    </section>
</main>
</body>
</html>
