<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user']['id'];

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

if (isset($_GET['take_job'])) {
    $orderId = (int) $_GET['take_job'];

    $stmt = $pdo->prepare("
        UPDATE orders
        SET traveler_id = ?, status = 'traveler_found'
        WHERE id = ?
        AND traveler_id IS NULL
        AND status = 'request_created'
    ");

    $stmt->execute([
        $userId,
        $orderId
    ]);

    header('Location: traveler.php');
    exit;
}

$totalOrder = getSingleValue(
    $pdo,
    "SELECT COUNT(*) FROM orders WHERE traveler_id = ?",
    [$userId]
);

$completedOrder = getSingleValue(
    $pdo,
    "SELECT COUNT(*) FROM orders WHERE traveler_id = ? AND status = 'completed'",
    [$userId]
);

$successRate = $totalOrder > 0 ? round(($completedOrder / $totalOrder) * 100, 1) : 0;

$averageRating = getSingleValue(
    $pdo,
    "SELECT ROUND(AVG(rating), 1) FROM traveler_reviews WHERE traveler_id = ?",
    [$userId],
    0
);

$totalReviews = getSingleValue(
    $pdo,
    "SELECT COUNT(*) FROM traveler_reviews WHERE traveler_id = ?",
    [$userId],
    0
);

$monthlyIncome = getSingleValue(
    $pdo,
    "SELECT COALESCE(SUM(service_fee), 0)
     FROM orders
     WHERE traveler_id = ?
     AND status = 'completed'
     AND MONTH(created_at) = MONTH(CURDATE())
     AND YEAR(created_at) = YEAR(CURDATE())",
    [$userId],
    0
);

$escrowHeld = getSingleValue(
    $pdo,
    "SELECT COALESCE(SUM(service_fee), 0)
     FROM orders
     WHERE traveler_id = ?
     AND status IN ('traveler_found', 'escrow_paid', 'qc_pending', 'on_delivery')",
    [$userId],
    0
);

$readyToWithdraw = getSingleValue(
    $pdo,
    "SELECT COALESCE(SUM(service_fee), 0)
     FROM orders
     WHERE traveler_id = ?
     AND status = 'completed'",
    [$userId],
    0
);

$activeTasks = getRows(
    $pdo,
    "SELECT *
     FROM orders
     WHERE traveler_id = ?
     AND status NOT IN ('completed', 'cancelled')
     ORDER BY id DESC
     LIMIT 5",
    [$userId]
);

$newRequests = getRows(
    $pdo,
    "SELECT *
     FROM orders
     WHERE status = 'request_created'
     AND traveler_id IS NULL
     ORDER BY id DESC
     LIMIT 4"
);

$activeTrip = getRows(
    $pdo,
    "SELECT *
     FROM trips
     WHERE traveler_id = ?
     AND trip_date >= CURDATE()
     ORDER BY trip_date ASC
     LIMIT 1",
    [$userId]
);

$activeTripData = $activeTrip[0] ?? null;

$withdrawHistory = getRows(
    $pdo,
    "SELECT *
     FROM orders
     WHERE traveler_id = ?
     AND status = 'completed'
     ORDER BY id DESC
     LIMIT 3",
    [$userId]
);

$kycStatus = getSingleValue(
    $pdo,
    "SELECT verification_status
     FROM traveler_kyc
     WHERE user_id = ?
     ORDER BY id DESC
     LIMIT 1",
    [$userId],
    'pending'
);

$statusLabel = [
    'request_created' => 'Request Baru',
    'traveler_found' => 'Traveler Dipilih',
    'escrow_paid' => 'Escrow Dibayar',
    'qc_pending' => 'Menunggu QC',
    'on_delivery' => 'Dalam Pengiriman',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard & Misi Traveler - TitipGo</title>
    <link rel="stylesheet" href="css/traveler_page.css">
</head>
<body>
<main class="app">
    <?php include 'includes/traveler_sidebar.php'; ?>

    <section class="main">
        <header class="top">
            <div>
                <p class="page-label">TRAVELER DASHBOARD</p>
                <h1>Dashboard & Misi</h1>
            </div>

            <a class="user-link" href="edit_profil.php">
                Hi, <?= htmlspecialchars($_SESSION['user']['name']); ?>
                <span class="user-icon">◎</span>
            </a>
        </header>

        <section class="hero-dashboard">
            <div>
                <h2>Pusat Kontrol Aktivitas Traveler</h2>
                <p>Pantau performa, pendapatan escrow, misi aktif, dan notifikasi penting dalam satu halaman.</p>
            </div>

            <a href="manajemen_rute.php" class="btn btn-light">
                + Kelola Rute
            </a>
        </section>

        <section class="stats">
            <article class="stat">
                <p>Total Order Diterima</p>
                <h3><?= (int) $totalOrder; ?>x</h3>
                <span class="muted">Semua pesanan yang pernah masuk</span>
            </article>

            <article class="stat">
                <p>Order Selesai</p>
                <h3 class="green"><?= (int) $completedOrder; ?>x</h3>
                <span class="muted">Transaksi berhasil diselesaikan</span>
            </article>

            <article class="stat">
                <p>Tingkat Keberhasilan</p>
                <h3 class="green"><?= $successRate; ?>%</h3>
                <span class="muted">Dihitung dari order selesai</span>
            </article>

            <article class="stat">
                <p>Rating Buyer</p>
                <h3>⭐ <?= $averageRating ?: '0.0'; ?></h3>
                <span class="muted"><?= (int) $totalReviews; ?> ulasan pembeli</span>
            </article>
        </section>

        <section class="income-grid">
            <article class="income-card blue-card">
                <p>Total Pendapatan Bulan Ini</p>
                <h2><?= rupiah($monthlyIncome); ?></h2>
                <span>Berhasil dari order yang sudah selesai bulan ini.</span>
            </article>

            <article class="income-card dark-card">
                <p>Dana Ditahan Escrow</p>
                <h2><?= rupiah($escrowHeld); ?></h2>
                <span>Dana masih menunggu proses QC, pengiriman, atau konfirmasi buyer.</span>
            </article>

            <article class="income-card green-card">
                <p>Siap Dicairkan</p>
                <h2><?= rupiah($readyToWithdraw); ?></h2>
                <span>Dana dari order selesai dan siap masuk ke rekening traveler.</span>
            </article>
        </section>

        <section class="dashboard-grid">
            <div>
                <section class="card route-card">
                    <div>
                        <h2>Rute Perjalanan Aktif</h2>

                        <?php if ($activeTripData): ?>
                            <h3>
                                ✈️ <?= htmlspecialchars($activeTripData['origin_country']); ?>
                                →
                                <?= htmlspecialchars($activeTripData['destination_city']); ?>
                            </h3>
                            <p class="muted">
                                Berangkat:
                                <?= date('d M Y', strtotime($activeTripData['trip_date'])); ?>
                                •
                                <?= htmlspecialchars($activeTripData['capacity_note']); ?>
                            </p>
                        <?php else: ?>
                            <h3>Belum ada rute aktif</h3>
                            <p class="muted">Tambahkan rute agar buyer dapat menemukan Anda di Discovery Produk.</p>
                        <?php endif; ?>
                    </div>

                    <a class="btn" href="manajemen_rute.php">
                        Manajemen Rute
                    </a>
                </section>

                <section class="mission-section">
                    <div class="section-head">
                        <h2>Misi Aktif</h2>
                        <a href="traveler_sop.php">Lihat Tugas SOP</a>
                    </div>

                    <?php if (empty($activeTasks)): ?>
                        <div class="empty-state">
                            <b>Belum ada misi aktif.</b>
                            <p>Ambil request dari buyer untuk mulai menjalankan tugas SOP.</p>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($activeTasks as $task): ?>
                        <article class="mission">
                            <div>
                                <b>#ORD-<?= str_pad($task['id'], 3, '0', STR_PAD_LEFT); ?>: <?= htmlspecialchars($task['product_name']); ?></b>
                                <p class="muted">
                                    <?= htmlspecialchars($task['product_country']); ?>
                                    •
                                    <?= rupiah($task['product_price']); ?>
                                </p>
                            </div>

                            <span class="queue">
                                <?= htmlspecialchars($statusLabel[$task['status']] ?? $task['status']); ?>
                            </span>

                            <a class="btn btn-dark" href="traveler_sop.php?order_id=<?= $task['id']; ?>">
                                Kerjakan
                            </a>
                        </article>
                    <?php endforeach; ?>
                </section>

                <section class="mission-section">
                    <div class="section-head">
                        <h2>Request Baru dari Buyer</h2>
                        <span class="muted">Misi potensial yang bisa diambil traveler.</span>
                    </div>

                    <?php if (empty($newRequests)): ?>
                        <div class="empty-state">
                            <b>Belum ada request baru.</b>
                            <p>Request buyer akan muncul otomatis saat tersedia.</p>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($newRequests as $request): ?>
                        <article class="mission">
                            <div>
                                <b><?= htmlspecialchars($request['product_name']); ?></b>
                                <p class="muted">
                                    <?= htmlspecialchars($request['product_country']); ?>
                                    •
                                    <?= rupiah($request['product_price']); ?>
                                </p>
                            </div>

                            <span class="queue">
                                Request Baru
                            </span>

                            <a class="btn btn-dark" href="traveler.php?take_job=<?= $request['id']; ?>">
                                Ambil Job
                            </a>
                        </article>
                    <?php endforeach; ?>
                </section>
            </div>

            <aside class="side-panel">
                <section class="card notification-card">
                    <h2>Notifikasi Penting</h2>

                    <div class="notice">
                        <b>📦 Request Baru</b>
                        <p><?= count($newRequests); ?> request buyer tersedia untuk Anda ambil.</p>
                    </div>

                    <div class="notice">
                        <b>💬 Pesan Buyer</b>
                        <p>Cek Tugas SOP untuk melihat chat dan bukti QC terbaru.</p>
                    </div>

                    <div class="notice">
                        <b>🛡️ Status KYC</b>
                        <p>Status verifikasi Anda: <strong><?= htmlspecialchars(ucfirst($kycStatus)); ?></strong></p>
                    </div>

                    <div class="notice">
                        <b>🏦 Escrow Siap Cair</b>
                        <p><?= rupiah($readyToWithdraw); ?> siap dicairkan melalui Dompet Escrow.</p>
                    </div>
                </section>

                <section class="card withdraw-card">
                    <h2>Riwayat Pencairan</h2>

                    <?php if (empty($withdrawHistory)): ?>
                        <div class="empty-mini">
                            Belum ada pencairan dana.
                        </div>
                    <?php endif; ?>

                    <?php foreach ($withdrawHistory as $withdraw): ?>
                        <div class="withdraw-row">
                            <div>
                                <b><?= htmlspecialchars($withdraw['product_name']); ?></b>
                                <span><?= htmlspecialchars($withdraw['status']); ?></span>
                            </div>
                            <strong><?= rupiah($withdraw['service_fee']); ?></strong>
                        </div>
                    <?php endforeach; ?>

                    <a href="dompet_escrow.php" class="btn btn-full">
                        Buka Dompet Escrow
                    </a>
                </section>
            </aside>
        </section>
    </section>
</main>
</body>
</html>
