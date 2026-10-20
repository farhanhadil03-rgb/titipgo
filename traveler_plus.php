<?php
require_once 'includes/traveler_auth.php';

$travelerId = $user['id'];
$successInfo = '';
$errorInfo = '';

function tableExists($pdo, $table)
{
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
        ");
        $stmt->execute([$table]);

        return (int) $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
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

function rupiah($number)
{
    return 'Rp ' . number_format((float) $number, 0, ',', '.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $packageName = $_POST['package_name'] ?? 'Super Traveler 3 Bulan';
    $amount = (float) ($_POST['amount'] ?? 199000);
    $paymentSuccess = true;

    if ($paymentSuccess) {
        try {
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

            $successInfo = 'Langganan Super Traveler berhasil aktif. Akun Anda mendapatkan prioritas pencarian dan badge premium.';
        } catch (PDOException $e) {
            $errorInfo = 'Gagal menyimpan langganan. Import database/traveler_plus_update.sql terlebih dahulu.';
        }
    }
}

$currentSubscription = null;

try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM traveler_subscriptions
        WHERE traveler_id = ?
        AND status = 'active'
        AND end_date >= CURDATE()
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([$travelerId]);
    $currentSubscription = $stmt->fetch();
} catch (PDOException $e) {
    $currentSubscription = null;
}

$topProduct = getSingleValue(
    $pdo,
    "SELECT product_name
     FROM orders
     WHERE traveler_id = ?
     GROUP BY product_name
     ORDER BY COUNT(*) DESC
     LIMIT 1",
    [$travelerId],
    'Belum ada data'
);

$topCountry = getSingleValue(
    $pdo,
    "SELECT product_country
     FROM orders
     WHERE traveler_id = ?
     GROUP BY product_country
     ORDER BY COUNT(*) DESC
     LIMIT 1",
    [$travelerId],
    'Belum ada data'
);

$totalOrders = getSingleValue(
    $pdo,
    "SELECT COUNT(*)
     FROM orders
     WHERE traveler_id = ?",
    [$travelerId],
    0
);

$completedOrders = getSingleValue(
    $pdo,
    "SELECT COUNT(*)
     FROM orders
     WHERE traveler_id = ?
     AND status = 'completed'",
    [$travelerId],
    0
);

$conversionRate = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 1) : 0;

$broadcastCount = getSingleValue(
    $pdo,
    "SELECT COUNT(*)
     FROM products
     WHERE traveler_id = ?",
    [$travelerId],
    0
);

$isPremium = $currentSubscription ? true : false;
$broadcastLimit = $isPremium ? '50 Produk' : '5 Produk';
$matchingPriority = $isPremium ? 'Prioritas Tinggi' : 'Standar';
$searchPriority = $isPremium ? 'Muncul Lebih Atas' : 'Urutan Normal';
$badgeName = $isPremium ? 'Premium Traveler' : 'Regular Traveler';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jastip Plus Premium Traveler - TitipGo</title>
    <link rel="stylesheet" href="css/traveler_plus.css">
</head>
<body>
<main class="premium-app">
    <aside class="premium-sidebar">
        <div class="brand">TitipGo</div>
        <p class="hub">TRAVELER PREMIUM</p>

        <nav class="premium-menu">
            <a class="active" href="traveler_plus.php">🔥 Jastip Plus Premium</a>
            <a href="traveler.php">↩️ Dashboard & Misi</a>
            <a href="local_finder.php">📍 Local Finder</a>
            <a href="dompet_escrow.php">💰 Dompet Escrow</a>
            <a href="manajemen_rute.php">🛫 Manajemen Rute</a>
            <a href="edit_profil.php">⚙️ Edit Profil</a>
        </nav>
    </aside>

    <section class="premium-main">
        <header class="topbar">
            <a class="back-btn" href="traveler.php">⬅ Kembali ke Hub</a>
            <a class="user-link" href="edit_profil.php">
                Hi! Jastiper
                <span class="user-icon">◎</span>
            </a>
        </header>

        <section class="hero-premium">
            <p class="eyebrow">SUPER TRAVELER SUBSCRIPTION</p>
            <h1>Tingkatkan Peluang Order dan Pendapatan</h1>
            <p>
                Layanan berlangganan khusus traveler untuk mendapatkan prioritas pencarian, prioritas matching,
                badge premium, analitik performa, dan batas broadcast produk yang lebih besar.
            </p>
        </section>

        <?php if ($successInfo): ?>
            <div class="notice-green"><?= htmlspecialchars($successInfo); ?></div>
        <?php endif; ?>

        <?php if ($errorInfo): ?>
            <div class="notice-red"><?= htmlspecialchars($errorInfo); ?></div>
        <?php endif; ?>

        <?php if ($currentSubscription): ?>
            <section class="premium-status-card">
                <div>
                    <h2>⭐ Premium Aktif</h2>
                    <p>
                        Paket:
                        <?= htmlspecialchars($currentSubscription['package_name']); ?>
                        • Berlaku sampai:
                        <?= date('d M Y', strtotime($currentSubscription['end_date'])); ?>
                    </p>
                </div>

                <span>Badge Premium Aktif</span>
            </section>
        <?php endif; ?>

        <section class="benefit-grid">
            <article class="benefit-card">
                <div class="benefit-icon">🔍</div>
                <h3>Prioritas Pencarian</h3>
                <p>Akun traveler premium muncul lebih atas pada hasil pencarian buyer.</p>
            </article>

            <article class="benefit-card">
                <div class="benefit-icon">⚡</div>
                <h3>Prioritas Matching</h3>
                <p>Request buyer lebih dahulu direkomendasikan kepada traveler premium.</p>
            </article>

            <article class="benefit-card">
                <div class="benefit-icon">🏅</div>
                <h3>Badge Premium</h3>
                <p>Memberikan kesan lebih profesional, terpercaya, dan mudah dipilih buyer.</p>
            </article>

            <article class="benefit-card">
                <div class="benefit-icon">📢</div>
                <h3>Broadcast Lebih Banyak</h3>
                <p>Traveler premium dapat mengunggah lebih banyak produk di Local Finder.</p>
            </article>
        </section>

        <section class="premium-layout">
            <section class="analytics-card">
                <h2>Analitik Performa Traveler</h2>
                <p class="analytics-desc">Pantau data penting agar traveler bisa memilih rute dan produk yang lebih menguntungkan.</p>

                <div class="analytics-grid">
                    <div class="analytics-box">
                        <span>Produk Paling Laris</span>
                        <h3><?= htmlspecialchars($topProduct); ?></h3>
                    </div>

                    <div class="analytics-box">
                        <span>Negara Permintaan Tertinggi</span>
                        <h3><?= htmlspecialchars($topCountry); ?></h3>
                    </div>

                    <div class="analytics-box">
                        <span>Rasio Konversi Order</span>
                        <h3><?= htmlspecialchars((string) $conversionRate); ?>%</h3>
                    </div>

                    <div class="analytics-box">
                        <span>Produk Local Finder</span>
                        <h3><?= (int) $broadcastCount; ?> Produk</h3>
                    </div>
                </div>

                <div class="feature-compare">
                    <div>
                        <span>Prioritas Pencarian</span>
                        <b><?= htmlspecialchars($searchPriority); ?></b>
                    </div>

                    <div>
                        <span>Prioritas Matching</span>
                        <b><?= htmlspecialchars($matchingPriority); ?></b>
                    </div>

                    <div>
                        <span>Limit Broadcast</span>
                        <b><?= htmlspecialchars($broadcastLimit); ?></b>
                    </div>

                    <div>
                        <span>Status Badge</span>
                        <b><?= htmlspecialchars($badgeName); ?></b>
                    </div>
                </div>
            </section>

            <aside class="plan-card">
                <span class="label-best">BEST VALUE</span>
                <h2>Super Traveler</h2>
                <h3>Rp 199.000</h3>
                <p class="plan-period">/3 bulan</p>

                <hr>

                <p>✓ Prioritas pencarian buyer</p>
                <p>✓ Prioritas matching request</p>
                <p>✓ Badge Premium Traveler</p>
                <p>✓ Analitik performa</p>
                <p>✓ Broadcast produk lebih banyak</p>
                <p>✓ Promo dan insentif khusus</p>

                <a class="btn btn-full yellow-btn" href="traveler_plus_payment.php?package=super3">
                    Mulai Berlangganan
                </a>

                <small>Dapat dibatalkan kapan saja.</small>
            </aside>
        </section>

        <section class="incentive-card">
            <h2>Promo dan Insentif</h2>
            <div class="incentive-grid">
                <div>💸 Komisi platform lebih rendah</div>
                <div>🎁 Bonus transaksi tertentu</div>
                <div>🔐 Akses fitur eksklusif</div>
            </div>
        </section>
    </section>
</main>
</body>
</html>
