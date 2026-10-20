<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$package = $_GET['package'] ?? $_POST['package'] ?? 'yearly';

if ($package === 'monthly') {
    $packageName = 'Paket Bulanan Premium';
    $dbPackageName = 'Bulanan';
    $amount = 49000;
    $durationText = '1 Bulan (30 Hari)';
    $endDate = date('Y-m-d', strtotime('+1 month'));
} else {
    $package = 'yearly';
    $packageName = 'Paket Tahunan Premium';
    $dbPackageName = 'Tahunan';
    $amount = 399000;
    $durationText = '1 Tahun (365 Hari Penuh)';
    $endDate = date('Y-m-d', strtotime('+1 year'));
}

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod = $_POST['payment_method'] ?? 'qris';

    try {
        $pdo->beginTransaction();

        $expireOld = $pdo->prepare("
            UPDATE subscriptions
            SET status = 'expired'
            WHERE user_id = ?
            AND status = 'active'
        ");
        $expireOld->execute([$_SESSION['user']['id']]);

        $insert = $pdo->prepare("
            INSERT INTO subscriptions
            (
                user_id,
                package_name,
                amount,
                start_date,
                end_date,
                status
            )
            VALUES
            (
                ?,
                ?,
                ?,
                CURDATE(),
                ?,
                'active'
            )
        ");

        $insert->execute([
            $_SESSION['user']['id'],
            $dbPackageName,
            $amount,
            $endDate
        ]);

        $pdo->commit();

        header('Location: jastip_plus.php?success=1');
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $errorMessage = 'Pembayaran berhasil diproses, tetapi data premium gagal disimpan.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metode Pembayaran Langganan - TitipGo</title>
    <link rel="stylesheet" href="css/plus_payment.css">
</head>
<body>
<main class="payment-app">
    <aside class="payment-sidebar">
        <div class="brand">TitipGo</div>

        <nav class="payment-menu">
            <a class="active" href="jastip_plus.php">🔥 Jastip Plus Premium</a>
            <a href="buyer.php">↩️ Kembali ke Beranda</a>
        </nav>
    </aside>

    <section class="payment-main">
        <header class="payment-topbar">
            <h1>Metode Pembayaran Langganan</h1>

            <a class="user-link" href="edit_profil_buyer.php">
                Hi, <?= htmlspecialchars($_SESSION['user']['name']); ?>
                <span class="user-icon">👑</span>
            </a>
        </header>

        <?php if ($errorMessage): ?>
            <div class="payment-alert error-alert">
                <?= htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="payment-layout">
            <input type="hidden" name="package" value="<?= htmlspecialchars($package); ?>">

            <section class="payment-card">
                <h2>Pilih Metode Pembayaran Instan</h2>

                <p class="section-label">DOMPET DIGITAL & QRIS INSTAN</p>

                <label class="payment-option active">
                    <input type="radio" name="payment_method" value="qris" checked>
                    <span class="circle"></span>
                    <div>
                        <b>QRIS (Gopay, OVO, Dana, LinkAja, ShopeePay)</b>
                        <small>Bebas biaya penanganan • Verifikasi Otomatis Detik Itu Juga</small>
                    </div>
                </label>

                <label class="payment-option">
                    <input type="radio" name="payment_method" value="gopay">
                    <span class="circle"></span>
                    <div>
                        <b>GoPay Instan Klik (Saldo Langsung Terpotong)</b>
                    </div>
                </label>

                <p class="section-label">TRANSFER VIRTUAL ACCOUNT (E-BANKING)</p>

                <label class="payment-option">
                    <input type="radio" name="payment_method" value="bca_va">
                    <span class="circle"></span>
                    <div>
                        <b>BCA Virtual Account</b>
                    </div>
                </label>

                <label class="payment-option">
                    <input type="radio" name="payment_method" value="mandiri_va">
                    <span class="circle"></span>
                    <div>
                        <b>Mandiri Virtual Account</b>
                    </div>
                </label>

                <p class="section-label">KARTU KREDIT / DEBIT ONLINE</p>

                <label class="payment-option">
                    <input type="radio" name="payment_method" value="card">
                    <span class="circle"></span>
                    <div>
                        <b>Visa / MasterCard / American Express</b>
                    </div>
                </label>
            </section>

            <aside class="summary-card">
                <h2>Rincian Berlangganan</h2>

                <div class="selected-package">
                    <p>PAKET DIPILIH</p>
                    <h3><?= htmlspecialchars($packageName); ?></h3>
                    <span>Masa aktif: <?= htmlspecialchars($durationText); ?></span>
                </div>

                <div class="summary-row">
                    <span>Harga Langganan</span>
                    <b>Rp <?= number_format($amount, 0, ',', '.'); ?></b>
                </div>

                <div class="summary-row">
                    <span>Pajak (PPN 11%)</span>
                    <b>Termasuk</b>
                </div>

                <div class="summary-row green-text">
                    <span>Biaya Administrasi</span>
                    <b>Gratis</b>
                </div>

                <div class="summary-total">
                    <span>Total Pembayaran</span>
                    <strong>Rp <?= number_format($amount, 0, ',', '.'); ?></strong>
                </div>

                <button class="pay-button" type="submit">
                    Bayar & Aktifkan VIP Sekarang
                </button>
            </aside>
        </form>
    </section>
</main>

<script>
document.querySelectorAll('.payment-option').forEach(function(option) {
    option.addEventListener('click', function() {
        document.querySelectorAll('.payment-option').forEach(function(item) {
            item.classList.remove('active');
        });

        option.classList.add('active');
        option.querySelector('input').checked = true;
    });
});
</script>
</body>
</html>
