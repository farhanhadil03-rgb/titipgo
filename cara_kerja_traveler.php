<?php
$travelerSteps = [
    ['no' => '1', 'title' => 'Posting Rute Trip', 'desc' => 'Masukkan jadwal perjalanan, kota asal, tujuan, dan kapasitas bagasi.', 'icon' => '✈️'],
    ['no' => '2', 'title' => 'Terima Match Order', 'desc' => 'Traveler melihat request buyer yang sesuai dengan rute perjalanan.', 'icon' => '💼'],
    ['no' => '3', 'title' => 'Belanja & Validasi Visual', 'desc' => 'Belikan barang dan upload bukti visual untuk transparansi.', 'icon' => '📸'],
    ['no' => '4', 'title' => 'Kirim Paket & Cair', 'desc' => 'Serahkan barang ke buyer lalu komisi jasa dapat dicairkan.', 'icon' => '🌕'],
];
$pageTitle = 'TitipGo - Cara Kerja Traveler';
$pageCss = 'cara_kerja_traveler.css';
$currentPage = 'traveler-flow';
require_once 'includes/header.php';
?>
<main class="section-container page-space work-section">
    <div class="section-heading">
        <h1>Bagaimana Cara Kerjanya?</h1>
        <p>Traveler dapat membuka rute perjalanan dan menerima request titipan.</p>
        <div class="pill-group">
            <a href="cara_kerja_buyer.php" class="pill">Bagi Pembeli</a>
            <a href="cara_kerja_traveler.php" class="pill active">Bagi Traveler</a>
        </div>
    </div>
    <div class="timeline-card">
        <?php foreach ($travelerSteps as $step): ?>
            <article class="step-card">
                <span class="step-number"><?= $step['no']; ?></span>
                <h3><?= htmlspecialchars($step['title']); ?></h3>
                <p><?= htmlspecialchars($step['desc']); ?></p>
                <div class="step-icon"><?= $step['icon']; ?></div>
            </article>
        <?php endforeach; ?>
    </div>
    <div class="center-button"><a href="register.php?role=traveler" class="btn btn-green btn-wide">Mulai Cari Orderan</a></div>
</main>
<?php require_once 'includes/footer.php'; ?>
