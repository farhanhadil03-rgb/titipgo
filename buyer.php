<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$countries = $pdo->query("SELECT DISTINCT country FROM country_cities ORDER BY country ASC")->fetchAll(PDO::FETCH_COLUMN);

if (empty($countries)) {
    $countries = $pdo->query("SELECT DISTINCT country FROM products ORDER BY country ASC")->fetchAll(PDO::FETCH_COLUMN);
}

$selectedCountry = $_GET['country'] ?? ($countries[0] ?? 'Japan');
$selectedCity = $_GET['city'] ?? '';

if ($selectedCity !== '') {
    $productStmt = $pdo->prepare("SELECT * FROM products WHERE country = ? AND city = ? ORDER BY id DESC LIMIT 6");
    $productStmt->execute([$selectedCountry, $selectedCity]);
} else {
    $productStmt = $pdo->prepare("SELECT * FROM products WHERE country = ? ORDER BY id DESC LIMIT 6");
    $productStmt->execute([$selectedCountry]);
}

$products = $productStmt->fetchAll();

$travelersStmt = $pdo->prepare(
    "SELECT users.id, users.name, trips.origin_country, trips.destination_city, trips.trip_date, trips.capacity_note
     FROM trips
     JOIN users ON users.id = trips.traveler_id
     WHERE users.role = 'traveler'
       AND trips.origin_country = ?
       AND trips.trip_date >= CURDATE()
     ORDER BY trips.trip_date ASC
     LIMIT 3"
);
$travelersStmt->execute([$selectedCountry]);
$travelers = $travelersStmt->fetchAll();

if (empty($travelers)) {
    $travelers = $pdo->query(
        "SELECT users.id, users.name, trips.origin_country, trips.destination_city, trips.trip_date, trips.capacity_note
         FROM trips
         JOIN users ON users.id = trips.traveler_id
         WHERE users.role = 'traveler'
           AND trips.trip_date >= CURDATE()
         ORDER BY trips.trip_date ASC
         LIMIT 3"
    )->fetchAll();
}

function flagEmoji($country) {
    $flags = [
        'Japan' => '🇯🇵',
        'Indonesia' => '🇮🇩',
        'Singapore' => '🇸🇬',
        'Thailand' => '🇹🇭',
        'South Korea' => '🇰🇷'
    ];

    return $flags[$country] ?? '🌏';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Page - TitipGo</title>
    <link rel="stylesheet" href="css/buyer_page.css">
</head>
<body>
<main class="app">
    <?php include 'sidebar_buyer.php'; ?>
    
    <section class="main">
        <div class="top">
            <input class="search" placeholder='✨ AI Search: Ketik "Oleh-oleh NTT" atau "Bakpia basah"...'>
            <div class="user">Hi, <?= htmlspecialchars($_SESSION['user']['name']); ?> <a href="edit_profil.php">○</a></div>
        </div>

        <div class="hero">
            <h1>Jelajahi produk tanpa batas</h1>
            <p>Titip belanja produk lokal maupun internasional dengan mudah melalui jaringan traveler terpercaya.</p>
        </div>

        <div class="chips">
            <h3>Ditemukan Untukmu di</h3>
            <?php foreach ($countries as $country): ?>
                <a href="buyer.php?country=<?= urlencode($country); ?>#modal-<?= urlencode($country); ?>" class="chip <?= $country === $selectedCountry ? 'active' : ''; ?>">
                    <?= flagEmoji($country); ?> <?= htmlspecialchars($country); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="selected-location">
            Menampilkan produk dari:
            <b><?= htmlspecialchars($selectedCountry); ?></b>
            <?php if ($selectedCity !== ''): ?>
                • <b><?= htmlspecialchars($selectedCity); ?></b>
            <?php endif; ?>
        </div>

        <div class="content-grid">
            <div class="products">
                <?php if (empty($products)): ?>
                    <article class="product card empty-product">
                        <h3>Belum ada produk</h3>
                        <p>Belum ada produk untuk kota ini. Silakan pilih kota lain.</p>
                    </article>
                <?php endif; ?>

                <?php foreach ($products as $p): ?>
                    <a class="product-link" href="detail_produk.php?id=<?= urlencode($p['id']); ?>">
                        <article class="product card">
                            <div class="product-img">📦</div>
                            <h3><?= htmlspecialchars($p['name']); ?></h3>
                            <p>
                                <?= htmlspecialchars($p['description'] ?? 'Produk titipan'); ?>
                                <?php if (!empty($p['city'])): ?>
                                    <br><span class="city-name">📍 <?= htmlspecialchars($p['city']); ?></span>
                                <?php endif; ?>
                            </p>
                            <span class="price">Rp <?= number_format($p['price'], 0, ',', '.'); ?>/pcs</span>
                            <span class="detail-hint">Lihat Detail Produk →</span>
                        </article>
                    </a>
                <?php endforeach; ?>
            </div>

            <aside class="travelers card">
                <h3>Traveler Tiba Tercepat</h3>
                <?php if (empty($travelers)): ?>
                    <p>Belum ada traveler aktif untuk negara ini.</p>
                <?php endif; ?>

                <?php foreach ($travelers as $traveler): ?>
                    <div class="traveler-row">
                        <span class="avatar"></span>
                        <div>
                            <b><?= htmlspecialchars($traveler['name']); ?> (<?= htmlspecialchars($traveler['origin_country']); ?> → <?= htmlspecialchars($traveler['destination_city']); ?>)</b><br>
                            <span class="eta">ETA: <?= date('d M Y', strtotime($traveler['trip_date'])); ?> • <?= htmlspecialchars($traveler['capacity_note']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </aside>
        </div>
    </section>
</main>

<?php foreach ($countries as $country): ?>
    <?php
        $countryCitiesStmt = $pdo->prepare("SELECT * FROM country_cities WHERE country = ? ORDER BY active_travelers DESC, city ASC");
        $countryCitiesStmt->execute([$country]);
        $countryCities = $countryCitiesStmt->fetchAll();
    ?>
    <div id="modal-<?= urlencode($country); ?>" class="modal-overlay">
        <div class="location-modal">
            <div class="modal-head">
                <div>
                    <h2>Pilih Negara & Kota Asal</h2>
                    <p>Tampilkan produk dan jastip berdasarkan kota spesifik.</p>
                </div>
                <a href="buyer.php?country=<?= urlencode($country); ?>" class="close-modal">×</a>
            </div>

            <div class="modal-search">🔍 <?= htmlspecialchars($country); ?></div>

            <p class="modal-label">NEGARA POPULER</p>
            <div class="country-buttons">
                <?php foreach (array_slice($countries, 0, 3) as $popularCountry): ?>
                    <a class="country-button <?= $popularCountry === $country ? 'active' : ''; ?>" href="buyer.php?country=<?= urlencode($popularCountry); ?>#modal-<?= urlencode($popularCountry); ?>">
                        <?= flagEmoji($popularCountry); ?> <?= htmlspecialchars($popularCountry); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <p class="modal-label">DAFTAR KOTA DI <?= strtoupper(htmlspecialchars($country)); ?></p>
            <div class="city-list">
                <?php foreach ($countryCities as $city): ?>
                    <a class="city-row <?= ($country === $selectedCountry && $city['city'] === $selectedCity) ? 'active' : ''; ?>" href="buyer.php?country=<?= urlencode($country); ?>&city=<?= urlencode($city['city']); ?>">
                        <b><?= htmlspecialchars($city['emoji']); ?> <?= htmlspecialchars($city['city']); ?></b>
                        <span><?= htmlspecialchars($city['active_travelers']); ?> Jastiper Aktif</span>
                    </a>
                <?php endforeach; ?>
            </div>

            <a class="btn" href="buyer.php?country=<?= urlencode($country); ?>">Tampilkan Semua Kota</a>
        </div>
    </div>
<?php endforeach; ?>
</body>
</html>
