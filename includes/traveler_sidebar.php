<?php
$currentPage = basename($_SERVER['PHP_SELF']);

function activeMenu($fileName, $currentPage) {
    return $fileName === $currentPage ? 'active' : '';
}
?>
<aside class="sidebar">
    <div class="brand">TitipGo</div>
    <div class="hub">TRAVELER HUB</div>
    <nav class="menu">
        <a class="<?= activeMenu('traveler.php', $currentPage); ?>" href="traveler.php">📊 Dashboard & Misi</a>
        <a class="<?= activeMenu('traveler_sop.php', $currentPage); ?>" href="traveler_sop.php">📋 Tugas SOP</a>
        <a class="<?= activeMenu('local_finder.php', $currentPage); ?>" href="local_finder.php">📍 Local Finder</a>
        <a class="<?= activeMenu('dompet_escrow.php', $currentPage); ?>" href="dompet_escrow.php">🏦 Dompet Escrow</a>
        <a class="<?= activeMenu('verifikasi_kyc.php', $currentPage); ?>" href="verifikasi_kyc.php">🛡️ Verifikasi KYC</a>
        <a class="<?= activeMenu('manajemen_rute.php', $currentPage); ?>" href="manajemen_rute.php">✈️ Manajemen Rute</a>
        <a class="<?= activeMenu('traveler_plus.php', $currentPage); ?>" href="traveler_plus.php">💎 Jastip Plus Premium</a>
        <a class="<?= activeMenu('edit_profil.php', $currentPage); ?>" href="edit_profil.php">👤 Edit Profil & Sandi</a>
        <a href="dashboard.php">🔁 Pilih Peran</a>
        <a href="logout.php">🚪 Logout</a>
    </nav>
</aside>
