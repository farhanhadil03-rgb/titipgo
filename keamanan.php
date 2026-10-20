<?php
$security = [
    ['icon' => '🏦', 'title' => 'Sistem Escrow (Rekening Bersama)', 'desc' => 'Dana buyer ditahan sampai pesanan selesai agar transaksi lebih aman.'],
    ['icon' => '🧑‍💼', 'title' => 'Traveler Terverifikasi KYC', 'desc' => 'Traveler wajib melengkapi data identitas sebelum menerima titipan.'],
    ['icon' => '📷', 'title' => 'SOP Bukti Visual Wajib', 'desc' => 'Traveler dapat memberikan bukti foto barang untuk meningkatkan kepercayaan.'],
    ['icon' => '⚖️', 'title' => 'Pusat Mediasi Sengketa', 'desc' => 'Jika terjadi kendala, sistem membantu proses penyelesaian masalah.'],
];
$pageTitle = 'TitipGo - Keamanan';
$pageCss = 'keamanan.css';
$currentPage = 'keamanan';
require_once 'includes/header.php';
?>
<main class="section-container security-section">
    <div class="section-heading">
        <div class="shield">🛡️</div>
        <h1>100% Aman & Terproteksi</h1>
        <p>Transaksi dibuat lebih aman melalui escrow, verifikasi traveler, bukti visual, dan mediasi.</p>
    </div>
    <div class="security-grid">
        <?php foreach ($security as $item): ?>
            <article class="security-card">
                <span class="security-icon"><?= $item['icon']; ?></span>
                <div>
                    <h3><?= htmlspecialchars($item['title']); ?></h3>
                    <p><?= htmlspecialchars($item['desc']); ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</main>
<?php require_once 'includes/footer.php'; ?>
