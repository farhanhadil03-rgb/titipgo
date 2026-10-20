<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['role'])) {
    $selectedRole = $_GET['role'];

    if ($selectedRole === 'buyer' || $selectedRole === 'traveler') {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$selectedRole, $_SESSION['user']['id']]);

        $_SESSION['user']['role'] = $selectedRole;

        if ($selectedRole === 'buyer') {
            header('Location: buyer.php');
            exit;
        }

        header('Location: traveler.php');
        exit;
    }
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Peran - TitipGo</title>
    <link rel="stylesheet" href="css/dashboard_role.css">
</head>
<body>
<header class="topbar">
    <a class="logo" href="index.php">TitipGo</a>
    <a class="help" href="logout.php">Logout</a>
</header>

<main class="role-page">
    <h1 class="title">Pilih Peran Anda</h1>
    <p class="subtitle">Bagaimana Anda ingin menggunakan platform TitipGo hari ini?</p>

    <section class="role-grid">
        <article class="role-card">
            <div class="role-head buyer-head">🛍️</div>
            <div class="role-body">
                <h2>Saya Ingin Menitip</h2>
                <p>Titip belanja produk dari mana saja dengan mudah melalui Traveler terpercaya.</p>

                <div class="features">
                    <span><i class="check">✓</i>100% Proteksi Dana Escrow</span>
                    <span><i class="check">✓</i>Akses Live Tracking & QC Room</span>
                    <span><i class="check">✓</i>Jaminan Traveler Terverifikasi</span>
                </div>

                <a class="btn btn-blue" href="dashboard.php?role=buyer">Lanjut sebagai Pembeli</a>
            </div>
        </article>

        <article class="role-card">
            <div class="role-head traveler-head">✈️</div>
            <div class="role-body">
                <h2>Saya Ingin Jadi Traveler</h2>
                <p>Jadikan perjalanan Anda lebih bernilai dengan membantu pembeli dan menghasilkan komisi.</p>

                <div class="features">
                    <span><i class="check green-check">✓</i>Bebas Atur Rute & Jadwal Perjalanan</span>
                    <span><i class="check green-check">✓</i>Pencairan Dana Escrow Otomatis</span>
                    <span><i class="check green-check">✓</i>Tersedia Fitur Misi Harian & Broadcast</span>
                </div>

                <a class="btn btn-green" href="dashboard.php?role=traveler">Lanjut sebagai Traveler</a>
            </div>
        </article>
    </section>

    <p class="note">💡 Saat memilih peran, data role user juga langsung diperbarui di database.</p>
</main>
</body>
</html>
