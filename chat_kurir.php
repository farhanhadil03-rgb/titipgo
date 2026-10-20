<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Kurir Domestik - TitipGo</title>
    <link rel="stylesheet" href="css/buyer_feature_common.css">
    <link rel="stylesheet" href="css/chat_kurir.css">
</head>
<body>
<main class="app">
    <?php include 'sidebar_buyer.php'; ?>

    <section class="main">
        <header class="topbar">
            <h1>Chat Pengiriman Kurir Domestik</h1>
            <div class="user-area">Hi, <?= htmlspecialchars($_SESSION['user']['name']); ?> <a class="user-icon" href="edit_profil.php">♙</a></div>
        </header>

        <section class="page-content chat-layout">
            <div class="chat-card card">
                <div class="courier-head">
                    <span>🛵</span>
                    <div><b>Ahmad Subarkah (Kurir Instant)</b><br><small>● Sedang Mengendarai (In-Transit)</small></div>
                </div>
                <div class="message left">Halo Selamat Siang Kak, paket kain tenunnya sudah selesai serah terima dari jastiper ya. <span>13:05</span></div>
                <div class="message left">Sekarang saya langsung jalan ke alamat kakak. <span>13:05</span></div>
                <div class="message right">Baik pak Ahmad, terima kasih banyak informasinya. <span>✓✓ 13:08</span></div>
                <div class="message right">Kalau sudah sampai titik ke pos satpam cluster depan saja ya pak. <span>✓✓ 13:09</span></div>
                <div class="chat-input">Ketik balasan pesan untuk kurir armada domestik... <span>›</span></div>
            </div>

            <aside class="tracking-info card">
                <h2>Informasi Pelacakan Kurir</h2>
                <div class="map-box">📍 Posisi Kurir</div>
                <h3>Data Logistik Armada</h3>
                <div class="info-box"><small>Jenis Kendaraan / Plat Nomor</small><br><b>Honda Vario • B 4910 CXS</b></div>
                <div class="eta-box"><small>⏳ Perkiraan Tiba Di Lokasi</small><br><b>13:26 WIB</b></div>
            </aside>
        </section>
    </section>
</main>
</body>
</html>
