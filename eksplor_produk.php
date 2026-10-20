<?php
require_once 'includes/db.php';
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT 6")->fetchAll();
$pageTitle = 'TitipGo - Eksplor Produk';
$pageCss = 'eksplor_produk.css';
$currentPage = 'produk';
require_once 'includes/header.php';
?>
<main class="product-section">
    <div class="product-banner section-container">
        <h1>Eksplorasi Barang Unik</h1>
        <form class="search-box" action="eksplor_produk.php" method="get">
            <input type="text" name="q" placeholder='Cari "Bakpia", "Kimono", atau "Kopi"...'>
            <button type="submit">Cari</button>
        </form>
    </div>
    <div class="section-container product-layout">
        <aside class="filter-card">
            <h3>Filter Produk</h3>
            <p class="filter-title">Asal Barang</p>
            <label><input type="checkbox" checked> Indonesia</label>
            <label><input type="checkbox"> Japan</label>
            <label><input type="checkbox"> Thailand</label>
            <p class="filter-title">Kategori</p>
            <label><input type="checkbox" checked> Makanan & Oleh-oleh</label>
            <label><input type="checkbox"> Fashion / Kerajinan</label>
        </aside>

        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <article class="product-card">
                    <div class="product-image">📦</div>
                    <h3><?= htmlspecialchars($product['name']); ?></h3>
                    <p><?= htmlspecialchars($product['country']); ?></p>
                    <strong>Rp <?= number_format($product['price'], 0, ',', '.'); ?></strong>
                    <a href="register.php?role=buyer" class="product-btn">Titip Sekarang</a>
                </article>
            <?php endforeach; ?>
        </div>

        <aside class="request-card">
            <div class="pen-icon">✍️</div>
            <h3>Tidak Nemu Barangnya?</h3>
            <p>Buat request manual setelah login sebagai buyer.</p>
            <a href="register.php?role=buyer" class="btn btn-light full-width">Buat Request</a>
        </aside>
    </div>
</main>
<?php require_once 'includes/footer.php'; ?>
