<?php
require_once 'includes/traveler_auth.php';

$travelerId = $user['id'];
$message = '';
$error = '';

function columnExists($pdo, $table, $column)
{
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);

        return (int) $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function safeValue($row, $key, $default = '-')
{
    return isset($row[$key]) && $row[$key] !== '' ? $row[$key] : $default;
}

$hasReturnDate = columnExists($pdo, 'trips', 'return_date');
$hasMaxWeight = columnExists($pdo, 'trips', 'max_weight');
$hasMaxItems = columnExists($pdo, 'trips', 'max_items');
$hasCategories = columnExists($pdo, 'trips', 'categories');
$hasStatus = columnExists($pdo, 'trips', 'status');

if (isset($_GET['toggle'])) {
    $tripId = (int) $_GET['toggle'];
    $newStatus = $_GET['status'] === 'inactive' ? 'inactive' : 'active';

    if ($hasStatus) {
        $stmt = $pdo->prepare("
            UPDATE trips
            SET status = ?
            WHERE id = ?
            AND traveler_id = ?
        ");
        $stmt->execute([
            $newStatus,
            $tripId,
            $travelerId
        ]);

        header('Location: manajemen_rute.php');
        exit;
    } else {
        $error = 'Kolom status belum tersedia. Import database/manajemen_rute_update.sql terlebih dahulu.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $originCountry = trim($_POST['origin_country'] ?? '');
    $destinationCity = trim($_POST['destination_city'] ?? '');
    $tripDate = $_POST['trip_date'] ?? '';
    $returnDate = $_POST['return_date'] ?? null;
    $maxWeight = $_POST['max_weight'] ?? null;
    $maxItems = $_POST['max_items'] ?? null;
    $capacityNote = trim($_POST['capacity_note'] ?? '');
    $categories = isset($_POST['categories']) ? implode(', ', $_POST['categories']) : '';
    $status = $_POST['status'] ?? 'active';

    if ($originCountry === '' || $destinationCity === '' || $tripDate === '' || $capacityNote === '') {
        $error = 'Kota keberangkatan, kota tujuan, tanggal keberangkatan, dan kapasitas wajib diisi.';
    } else {
        try {
            $columns = [
                'traveler_id',
                'origin_country',
                'destination_city',
                'trip_date',
                'capacity_note'
            ];

            $values = [
                $travelerId,
                $originCountry,
                $destinationCity,
                $tripDate,
                $capacityNote
            ];

            if ($hasReturnDate) {
                $columns[] = 'return_date';
                $values[] = $returnDate;
            }

            if ($hasMaxWeight) {
                $columns[] = 'max_weight';
                $values[] = $maxWeight;
            }

            if ($hasMaxItems) {
                $columns[] = 'max_items';
                $values[] = $maxItems;
            }

            if ($hasCategories) {
                $columns[] = 'categories';
                $values[] = $categories;
            }

            if ($hasStatus) {
                $columns[] = 'status';
                $values[] = $status;
            }

            $placeholders = implode(', ', array_fill(0, count($columns), '?'));

            $sql = "
                INSERT INTO trips
                (
                    " . implode(', ', $columns) . "
                )
                VALUES
                (
                    $placeholders
                )
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            $message = 'Rute perjalanan berhasil ditambahkan dan akan muncul pada pencarian buyer jika status aktif.';
        } catch (PDOException $e) {
            $error = 'Gagal menyimpan rute. Pastikan struktur tabel trips sudah sesuai.';
        }
    }
}

try {
    if ($hasStatus) {
        $routeStmt = $pdo->prepare("
            SELECT *
            FROM trips
            WHERE traveler_id = ?
            ORDER BY trip_date DESC, id DESC
        ");
    } else {
        $routeStmt = $pdo->prepare("
            SELECT *
            FROM trips
            WHERE traveler_id = ?
            ORDER BY trip_date DESC, id DESC
        ");
    }

    $routeStmt->execute([$travelerId]);
    $routes = $routeStmt->fetchAll();
} catch (PDOException $e) {
    $routes = [];
}

$activeRoutes = 0;
$inactiveRoutes = 0;

foreach ($routes as $route) {
    $routeStatus = $route['status'] ?? 'active';

    if ($routeStatus === 'inactive') {
        $inactiveRoutes++;
    } else {
        $activeRoutes++;
    }
}

$categoryOptions = [
    'Fashion',
    'Elektronik',
    'Makanan',
    'Kosmetik'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Rute - TitipGo</title>
    <link rel="stylesheet" href="css/manajemen_rute.css">
</head>
<body>
<main class="app">
    <?php include 'includes/traveler_sidebar.php'; ?>

    <section class="main">
        <header class="topbar">
            <h1>Manajemen Rute</h1>
            <a class="user-link" href="edit_profil.php">
                Hi! Jastiper
                <span class="user-icon">◎</span>
            </a>
        </header>

        <section class="page-content route-layout">
            <section class="route-main">
                <div class="hero-route">
                    <div>
                        <p class="page-label">ROUTE MATCHING SYSTEM</p>
                        <h2>Hubungkan Perjalananmu dengan Request Buyer</h2>
                        <p>Sistem menggunakan data rute ini untuk mencocokkan traveler dengan request manual dan pencarian buyer.</p>
                    </div>
                </div>

                <div class="route-stats">
                    <article class="stat-box">
                        <p>Rute Aktif</p>
                        <h3><?= (int) $activeRoutes; ?></h3>
                    </article>

                    <article class="stat-box">
                        <p>Rute Nonaktif</p>
                        <h3><?= (int) $inactiveRoutes; ?></h3>
                    </article>

                    <article class="stat-box">
                        <p>Total Rute</p>
                        <h3><?= count($routes); ?></h3>
                    </article>
                </div>

                <section class="card">
                    <h2 class="section-title">Tambah Rute Baru</h2>

                    <?php if ($message): ?>
                        <div class="notice-green"><?= htmlspecialchars($message); ?></div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="notice-red"><?= htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST" class="route-form">
                        <div class="form-grid">
                            <div>
                                <label>Kota / Negara Keberangkatan</label>
                                <input class="input" name="origin_country" placeholder="Contoh: Jakarta" required>
                            </div>

                            <div>
                                <label>Kota / Negara Tujuan</label>
                                <input class="input" name="destination_city" placeholder="Contoh: Tokyo" required>
                            </div>

                            <div>
                                <label>Tanggal Keberangkatan</label>
                                <input class="input" type="date" name="trip_date" required>
                            </div>

                            <div>
                                <label>Tanggal Kepulangan</label>
                                <input class="input" type="date" name="return_date">
                            </div>

                            <div>
                                <label>Maksimal Berat Titipan</label>
                                <input class="input" type="number" name="max_weight" placeholder="Contoh: 10">
                            </div>

                            <div>
                                <label>Maksimal Jumlah Item</label>
                                <input class="input" type="number" name="max_items" placeholder="Contoh: 15">
                            </div>

                            <div class="full">
                                <label>Catatan Kapasitas</label>
                                <input class="input" name="capacity_note" placeholder="Contoh: Maksimal 10 kg atau 15 item" required>
                            </div>
                        </div>

                        <h3 class="mini-title">Kategori Barang yang Diterima</h3>

                        <div class="category-grid">
                            <?php foreach ($categoryOptions as $category): ?>
                                <label class="category-option">
                                    <input type="checkbox" name="categories[]" value="<?= htmlspecialchars($category); ?>">
                                    <?= htmlspecialchars($category); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <h3 class="mini-title">Status Rute</h3>

                        <div class="status-options">
                            <label class="status-option active-option">
                                <input type="radio" name="status" value="active" checked>
                                Aktif - Menerima Request Baru
                            </label>

                            <label class="status-option">
                                <input type="radio" name="status" value="inactive">
                                Nonaktif - Tidak Menerima Request
                            </label>
                        </div>

                        <button class="btn btn-full" type="submit">
                            Posting Jadwal Rute
                        </button>
                    </form>
                </section>

                <section class="card">
                    <h2 class="section-title">Daftar Rute Perjalanan</h2>

                    <?php if (empty($routes)): ?>
                        <div class="empty-route">
                            Belum ada rute perjalanan. Tambahkan rute baru agar buyer dapat menemukan Anda.
                        </div>
                    <?php endif; ?>

                    <div class="route-list">
                        <?php foreach ($routes as $route): ?>
                            <?php
                                $routeStatus = $route['status'] ?? 'active';
                                $toggleStatus = $routeStatus === 'active' ? 'inactive' : 'active';
                            ?>

                            <article class="route-card <?= $routeStatus === 'inactive' ? 'route-off' : ''; ?>">
                                <div>
                                    <h3>
                                        ✈️ <?= htmlspecialchars($route['origin_country']); ?>
                                        →
                                        <?= htmlspecialchars($route['destination_city']); ?>
                                    </h3>

                                    <p>
                                        Berangkat:
                                        <?= date('d M Y', strtotime($route['trip_date'])); ?>

                                        <?php if (!empty($route['return_date'])): ?>
                                            • Pulang:
                                            <?= date('d M Y', strtotime($route['return_date'])); ?>
                                        <?php endif; ?>
                                    </p>

                                    <p>
                                        Kapasitas:
                                        <?= htmlspecialchars(safeValue($route, 'capacity_note')); ?>

                                        <?php if (!empty($route['max_weight'])): ?>
                                            • <?= htmlspecialchars($route['max_weight']); ?> kg
                                        <?php endif; ?>

                                        <?php if (!empty($route['max_items'])): ?>
                                            • <?= htmlspecialchars($route['max_items']); ?> item
                                        <?php endif; ?>
                                    </p>

                                    <p>
                                        Kategori:
                                        <?= htmlspecialchars(safeValue($route, 'categories', 'Belum dipilih')); ?>
                                    </p>
                                </div>

                                <div class="route-action">
                                    <span class="status-badge <?= $routeStatus === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                        <?= $routeStatus === 'active' ? 'Aktif' : 'Nonaktif'; ?>
                                    </span>

                                    <a
                                        class="btn <?= $routeStatus === 'active' ? 'btn-dark' : ''; ?>"
                                        href="manajemen_rute.php?toggle=<?= urlencode($route['id']); ?>&status=<?= urlencode($toggleStatus); ?>"
                                    >
                                        <?= $routeStatus === 'active' ? 'Tutup Rute' : 'Aktifkan'; ?>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </section>

            <aside class="route-side">
                <section class="card dark-card">
                    <h2>Kenapa Rute Penting?</h2>
                    <p>
                        Rute aktif akan muncul pada Request Manual dan sistem pencarian buyer sehingga peluang menerima order lebih besar.
                    </p>
                </section>

                <section class="card">
                    <h2 class="section-title">Contoh Rute Populer</h2>

                    <div class="example-route">
                        <b>Jakarta → Tokyo</b>
                        <span>Fashion, makanan, merchandise anime</span>
                    </div>

                    <div class="example-route">
                        <b>Jakarta → Bangkok</b>
                        <span>Produk UMKM Thailand dan snack lokal</span>
                    </div>

                    <div class="example-route">
                        <b>Jakarta → Seoul</b>
                        <span>Kosmetik, skincare, dan fashion Korea</span>
                    </div>
                </section>

                <section class="card">
                    <h2 class="section-title">Tips Mendapat Order</h2>
                    <div class="info-box">
                        <b>Aktifkan kategori spesifik</b>
                        <p>Buyer lebih mudah menemukan traveler jika rute memiliki kategori barang yang jelas.</p>
                    </div>
                </section>
            </aside>
        </section>
    </section>
</main>
</body>
</html>
