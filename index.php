<?php
$benefits = [
    ['icon' => '🔒', 'title' => 'Platform Guarantee', 'desc' => 'Uang Anda ditahan oleh sistem Escrow hingga barang diterima dengan aman.'],
    ['icon' => '📷', 'title' => 'Real-Time QC Room', 'desc' => 'Chat dan minta foto kondisi barang langsung saat Jastiper di toko.'],
    ['icon' => '📦', 'title' => 'Fragile Mode (Kabin)', 'desc' => 'Opsi wajib bawa tas kabin pesawat untuk kue, keramik, dan barang rapuh.'],
    ['icon' => '🧑‍💼', 'title' => 'Traveler Terverifikasi', 'desc' => 'Seluruh Traveler melewati proses KYC untuk keamanan maksimal.'],
];
$pageTitle = 'TitipGo - Beranda';
$pageCss = 'beranda.css';
$currentPage = 'beranda';
require_once 'includes/header.php';
?>
<main>
    <section class="hero section-container">
        <div class="hero-text">
            <p class="eyebrow">PENGIRIMAN BERBASIS PERJALANAN MANUSIA</p>
            <h1>Titip barang, tapi kayak dibeliin teman sendiri.</h1>
            <p class="hero-desc">TitipGo mempertemukan buyer dengan traveler secara aman, transparan, dan praktis.</p>
            <div class="hero-buttons">
                <a href="register.php?role=buyer" class="btn btn-light">🛍️ Titip Pesanan Sekarang</a>
                <a href="register.php?role=traveler" class="btn btn-green">✈️ Jadi Traveler</a>
            </div>
        </div>
        <div class="hero-visual">
            <div class="circle circle-one"></div>
            <div class="circle circle-two"></div>
            <div class="package-box">📦</div>
            <div class="escrow-badge">🛡️ <b>Dana Terproteksi</b><br><span>Sistem Rekening Bersama</span></div>
        </div>
    </section>

    <section class="section-container benefit-section">
        <h2>Mengapa Memilih TitipGo?</h2>
        <div class="benefit-grid">
            <?php foreach ($benefits as $benefit): ?>
                <article class="mini-card">
                    <span class="icon-circle"><?= $benefit['icon']; ?></span>
                    <h3><?= htmlspecialchars($benefit['title']); ?></h3>
                    <p><?= htmlspecialchars($benefit['desc']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php require_once 'includes/footer.php'; ?>
