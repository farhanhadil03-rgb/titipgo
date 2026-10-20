<?php
require_once 'includes/traveler_auth.php';

$travelerId = $user['id'];
$successInfo = '';
$errorInfo = '';

function rupiah($number)
{
    return 'Rp ' . number_format((float) $number, 0, ',', '.');
}

function getSingleValue($pdo, $sql, $params = [], $default = 0)
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();

        return $value !== false && $value !== null ? $value : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

function getRows($pdo, $sql, $params = [])
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

$saldoDitahan = getSingleValue(
    $pdo,
    "SELECT COALESCE(SUM(service_fee), 0)
     FROM orders
     WHERE traveler_id = ?
     AND status IN ('traveler_found', 'escrow_paid', 'qc_in_progress', 'item_purchased', 'item_verified', 'on_delivery')",
    [$travelerId]
);

$saldoTersedia = getSingleValue(
    $pdo,
    "SELECT COALESCE(SUM(service_fee), 0)
     FROM orders
     WHERE traveler_id = ?
     AND status = 'completed'",
    [$travelerId]
);

$saldoDicairkan = getSingleValue(
    $pdo,
    "SELECT COALESCE(SUM(amount), 0)
     FROM withdrawal_requests
     WHERE traveler_id = ?
     AND status = 'approved'",
    [$travelerId]
);

$saldoBisaDitarik = max($saldoTersedia - $saldoDicairkan, 0);

try {
    $bankStmt = $pdo->prepare("
        SELECT bank_name, account_number
        FROM traveler_kyc
        WHERE user_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $bankStmt->execute([$travelerId]);
    $bankAccount = $bankStmt->fetch();
} catch (PDOException $e) {
    $bankAccount = null;
}

if (!$bankAccount) {
    $bankAccount = [
        'bank_name' => 'Bank BCA',
        'account_number' => '8829 **** ****'
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float) ($_POST['amount'] ?? 0);
    $bankName = trim($_POST['bank_name'] ?? '');
    $accountNumber = trim($_POST['account_number'] ?? '');

    if ($amount <= 0) {
        $errorInfo = 'Nominal pencairan harus lebih dari Rp 0.';
    } elseif ($amount > $saldoBisaDitarik) {
        $errorInfo = 'Nominal pencairan melebihi saldo tersedia.';
    } elseif ($bankName === '' || $accountNumber === '') {
        $errorInfo = 'Rekening tujuan wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO withdrawal_requests
                (
                    traveler_id,
                    amount,
                    bank_name,
                    account_number,
                    status,
                    created_at
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    'pending',
                    NOW()
                )
            ");

            $stmt->execute([
                $travelerId,
                $amount,
                $bankName,
                $accountNumber
            ]);

            $successInfo = 'Pengajuan pencairan dana berhasil dibuat dan sedang diproses.';
        } catch (PDOException $e) {
            $errorInfo = 'Gagal mengajukan pencairan. Import database/dompet_escrow_update.sql terlebih dahulu.';
        }
    }
}

$orderRows = getRows(
    $pdo,
    "SELECT id, product_name, product_country, product_price, service_fee, status, created_at
     FROM orders
     WHERE traveler_id = ?
     ORDER BY id DESC
     LIMIT 8",
    [$travelerId]
);

$withdrawRows = getRows(
    $pdo,
    "SELECT *
     FROM withdrawal_requests
     WHERE traveler_id = ?
     ORDER BY id DESC
     LIMIT 6",
    [$travelerId]
);

if (empty($orderRows)) {
    $orderRows = [
        [
            'id' => 891,
            'product_name' => 'Bakpia Pathok 25',
            'product_country' => 'Indonesia',
            'product_price' => 450000,
            'service_fee' => 25000,
            'status' => 'completed',
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 892,
            'product_name' => 'Kimono Yukata',
            'product_country' => 'Japan',
            'product_price' => 1525000,
            'service_fee' => 75000,
            'status' => 'on_delivery',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
}

function statusText($status)
{
    if ($status === 'completed') {
        return 'Saldo Tersedia';
    }

    if ($status === 'cancelled') {
        return 'Dibatalkan';
    }

    return 'Ditahan Escrow';
}

function statusClass($status)
{
    if ($status === 'completed') {
        return 'status-green';
    }

    if ($status === 'cancelled') {
        return 'status-red';
    }

    return 'status-yellow';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dompet Escrow - TitipGo</title>
    <link rel="stylesheet" href="css/dompet_escrow.css">
</head>
<body>
<main class="app">
    <?php include 'includes/traveler_sidebar.php'; ?>

    <section class="main">
        <header class="topbar">
            <h1>Dompet Escrow</h1>
            <a class="user-link" href="edit_profil.php">
                Hi! Jastiper
                <span class="user-icon">◎</span>
            </a>
        </header>

        <section class="page-content wallet-layout">
            <section class="wallet-main">
                <?php if ($successInfo): ?>
                    <div class="notice-green"><?= htmlspecialchars($successInfo); ?></div>
                <?php endif; ?>

                <?php if ($errorInfo): ?>
                    <div class="notice-red"><?= htmlspecialchars($errorInfo); ?></div>
                <?php endif; ?>

                <div class="stat-banner">
                    <div>
                        <p>Saldo Tersedia (Bisa Ditarik)</p>
                        <h2><?= rupiah($saldoBisaDitarik); ?></h2>
                        <span>Dana dari transaksi selesai yang belum dicairkan.</span>
                    </div>

                    <a class="btn btn-light" href="#withdraw-section">Tarik Dana</a>
                </div>

                <div class="balance-grid">
                    <article class="info-box">
                        <span class="muted">Saldo Ditahan</span>
                        <h2><?= rupiah($saldoDitahan); ?></h2>
                        <p>Dana masih berada dalam escrow sampai buyer menyelesaikan transaksi.</p>
                    </article>

                    <article class="info-box">
                        <span class="muted">Saldo Tersedia</span>
                        <h2><?= rupiah($saldoTersedia); ?></h2>
                        <p>Dana sudah bisa dicairkan karena order selesai.</p>
                    </article>

                    <article class="info-box">
                        <span class="muted">Saldo Dicairkan</span>
                        <h2><?= rupiah($saldoDicairkan); ?></h2>
                        <p>Dana yang sudah masuk atau sedang diproses ke rekening traveler.</p>
                    </article>
                </div>

                <section id="withdraw-section" class="card withdraw-form-card">
                    <h2 class="section-title">Ajukan Pencairan Dana</h2>
                    <p class="form-desc">Pilih nominal pencairan dan rekening tujuan yang sudah tervalidasi KYC.</p>

                    <form method="POST" class="withdraw-form">
                        <label>Nominal Pencairan</label>
                        <input class="input" type="number" name="amount" min="1" max="<?= htmlspecialchars((string) $saldoBisaDitarik); ?>" placeholder="Contoh: 250000" required>

                        <label>Bank Tujuan</label>
                        <input class="input" name="bank_name" value="<?= htmlspecialchars($bankAccount['bank_name'] ?? 'Bank BCA'); ?>" required>

                        <label>Nomor Rekening</label>
                        <input class="input" name="account_number" value="<?= htmlspecialchars($bankAccount['account_number'] ?? '8829 **** ****'); ?>" required>

                        <button class="btn btn-full" type="submit">Ajukan Withdraw</button>
                    </form>
                </section>

                <section class="card">
                    <h2 class="section-title">Riwayat Transaksi & Komisi</h2>

                    <div class="transaction-list">
                        <?php foreach ($orderRows as $row): ?>
                            <article class="transaction-card">
                                <div>
                                    <b>#ORD-<?= str_pad($row['id'], 3, '0', STR_PAD_LEFT); ?>: <?= htmlspecialchars($row['product_name']); ?></b>
                                    <p class="muted"><?= htmlspecialchars($row['product_country']); ?> • <?= date('d M Y', strtotime($row['created_at'])); ?></p>
                                </div>

                                <div class="transaction-price">
                                    <strong>+ <?= rupiah($row['service_fee']); ?></strong>
                                    <span class="<?= statusClass($row['status']); ?>"><?= htmlspecialchars(statusText($row['status'])); ?></span>
                                </div>

                                <a class="detail-link" href="traveler_sop.php?order_id=<?= urlencode($row['id']); ?>">Rincian Order</a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </section>

            <aside class="wallet-side">
                <section class="card">
                    <h2 class="section-title">Rekening Tujuan Validasi KYC</h2>

                    <div class="bank-card">
                        <b><?= htmlspecialchars($bankAccount['bank_name'] ?? 'Bank BCA'); ?></b>
                        <p>
                            <?= htmlspecialchars($bankAccount['account_number'] ?? '8829 **** ****'); ?>
                            <span>A.n. <?= htmlspecialchars($user['name']); ?></span>
                        </p>
                    </div>

                    <a class="btn btn-full" href="verifikasi_kyc.php">Perbarui KYC</a>
                </section>

                <section class="card">
                    <h2 class="section-title">Riwayat Pencairan</h2>

                    <?php if (empty($withdrawRows)): ?>
                        <div class="empty-withdraw">Belum ada pengajuan pencairan dana.</div>
                    <?php endif; ?>

                    <?php foreach ($withdrawRows as $withdraw): ?>
                        <div class="withdraw-row">
                            <div>
                                <b><?= rupiah($withdraw['amount']); ?></b>
                                <span><?= htmlspecialchars($withdraw['bank_name']); ?></span>
                            </div>

                            <strong class="<?= $withdraw['status'] === 'approved' ? 'status-green' : 'status-yellow'; ?>">
                                <?= htmlspecialchars(ucfirst($withdraw['status'])); ?>
                            </strong>
                        </div>
                    <?php endforeach; ?>
                </section>

                <section class="card dark-card">
                    <h2>Escrow Aman</h2>
                    <p>Dana buyer hanya dicairkan setelah transaksi selesai, bukti QC valid, dan barang diterima.</p>
                </section>
            </aside>
        </section>
    </section>
</main>
</body>
</html>
