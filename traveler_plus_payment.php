<?php
require_once 'includes/traveler_auth.php';

$travelerId = $user['id'];
$package = $_GET['package'] ?? $_POST['package'] ?? 'super3';

$packageName = 'Super Traveler 3 Bulan';
$amount = 199000;
$durationText = '3 Bulan (90 Hari)';
$endInterval = '3 MONTH';

$errorInfo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod = $_POST['payment_method'] ?? 'qris';

    try {
        $pdo->beginTransaction();

        $expireOld = $pdo->prepare("
            UPDATE traveler_subscriptions
            SET status = 'expired'
            WHERE traveler_id = ?
            AND status = 'active'
        ");
        $expireOld->execute([$travelerId]);

        $stmt = $pdo->prepare("
            INSERT INTO traveler_subscriptions
            (
                traveler_id,
                package_name,
                amount,
                start_date,
                end_date,
                status,
                created_at
            )
            VALUES
            (
                ?,
                ?,
                ?,
                CURDATE(),
                DATE_ADD(CURDATE(), INTERVAL 3 MONTH),
                'active',
                NOW()
            )
        ");

        $stmt->execute([
            $travelerId,
            $packageName,
            $amount
        ]);

        $pdo->commit();

        header('Location: traveler_plus.php?success=1');
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $errorInfo = 'Pembayaran berhasil diproses, tetapi data langganan traveler gagal disimpan.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metode Pembayaran Super Traveler - TitipGo</title>
    <link rel="stylesheet" href="css/plus_payment.css">
</head>
<body>
<main class="payment-app">
    <aside class="payment-sidebar">
        <div class="brand">TitipGo</div>

        <nav class="payment-menu">
            <a class="active" href="traveler_plus.php">🔥 Super Traveler Premium</a>
            <a href="traveler.php">↩️ Kembali ke Hub</a>
        </nav>
    </aside>

    <section class="payment-main">
        <header class="payment-topbar">
            <h1>Metode Pembayaran Langganan Traveler</h1>

            <a class="user-link" href="edit_profil.php">
                Hi! Jastiper
                <span class="user-icon">👑</span>
            </a>
        </header>

        <?php if ($errorInfo): ?>
            <div class="payment-alert error-alert">
                <?= htmlspecialchars($errorInfo); ?>
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
                    Bayar & Aktifkan Premium Traveler
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
