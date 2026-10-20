<?php
$buyerSteps = [
    ['no' => '1', 'title' => 'Cari / Request Produk', 'desc' => 'Pilih katalog lokal atau ketik bebas barang yang Anda inginkan.', 'icon' => '🛍️'],
    ['no' => '2', 'title' => 'Bayar ke Escrow', 'desc' => 'Sistem menahan uang buyer secara aman sebelum diteruskan ke traveler.', 'icon' => '🏦'],
    ['no' => '3', 'title' => 'Traveler Beli & QC Room', 'desc' => 'Traveler membeli barang dan buyer dapat meminta bukti kondisi barang.', 'icon' => '💬'],
    ['no' => '4', 'title' => 'Barang Diterima', 'desc' => 'Buyer konfirmasi barang sampai, lalu dana diteruskan ke traveler.', 'icon' => '📦'],
];
$pageTitle = 'TitipGo - Cara Kerja Buyer';
$pageCss = 'cara_kerja_buyer.css';
$currentPage = 'buyer-flow';
require_once 'includes/header.php';
?>
<main class="section-container page-space work-section">
    <div class="section-heading">
        <h1>Bagaimana Cara Kerjanya?</h1>
        <p>Sistem penitipan cerdas yang mempertemukan Pembeli dan Traveler.</p>
        <div class="pill-group">
            <a href="cara_kerja_buyer.php" class="pill active">Bagi Pembeli</a>
            <a href="cara_kerja_traveler.php" class="pill">Bagi Traveler</a>
        </div>
    </div>
    <div class="timeline-card">
        <?php foreach ($buyerSteps as $step): ?>
            <article class="step-card">
                <span class="step-number"><?= $step['no']; ?></span>
                <h3><?= htmlspecialchars($step['title']); ?></h3>
                <p><?= htmlspecialchars($step['desc']); ?></p>
                <div class="step-icon"><?= $step['icon']; ?></div>
            </article>
        <?php endforeach; ?>
    </div>
    <div class="center-button"><a href="register.php?role=buyer" class="btn btn-primary btn-wide">Coba Pesan Sekarang</a></div>
</main>
<?php require_once 'includes/footer.php'; ?>
