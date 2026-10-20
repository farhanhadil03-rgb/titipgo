<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$messageInfo = '';
$errorInfo = '';

$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;

if ($orderId > 0) {
    $orderStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND buyer_id = ? LIMIT 1");
    $orderStmt->execute([$orderId, $userId]);
} else {
    $orderStmt = $pdo->prepare("SELECT * FROM orders WHERE buyer_id = ? AND status NOT IN ('completed', 'cancelled') ORDER BY id DESC LIMIT 1");
    $orderStmt->execute([$userId]);
}

$currentOrder = $orderStmt->fetch();
$totalOrders = $currentOrder ? 1 : 0;

if ($currentOrder && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $chatMessage = trim($_POST['message'] ?? '');
    $photoPath = null;

    if (!empty($_FILES['photo']['name'])) {
        $uploadDir = __DIR__ . '/uploads/qc/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

        if (in_array($extension, $allowedExtensions)) {
            $fileName = 'buyer_qc_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                $photoPath = 'uploads/qc/' . $fileName;
            }
        } else {
            $errorInfo = 'Format foto harus JPG, JPEG, PNG, atau WEBP.';
        }
    }

    if ($chatMessage !== '' || $photoPath !== null) {
        $insertChat = $pdo->prepare("INSERT INTO qc_messages (order_id, sender_id, sender_role, message, photo_path) VALUES (?, ?, 'buyer', ?, ?)");
        $insertChat->execute([$currentOrder['id'], $userId, $chatMessage, $photoPath]);
        header('Location: qc_room.php?order_id=' . $currentOrder['id']);
        exit;
    }
}

if ($currentOrder && isset($_GET['confirm'])) {
    if ($_GET['confirm'] === 'ok') {
        $update = $pdo->prepare("UPDATE orders SET status = 'qc_approved' WHERE id = ? AND buyer_id = ?");
        $update->execute([$currentOrder['id'], $userId]);
        header('Location: checkout_pembayaran.php?order_id=' . $currentOrder['id']);
        exit;
    }

    if ($_GET['confirm'] === 'reject') {
        $update = $pdo->prepare("UPDATE orders SET status = 'qc_rejected' WHERE id = ? AND buyer_id = ?");
        $update->execute([$currentOrder['id'], $userId]);
        header('Location: qc_room.php?order_id=' . $currentOrder['id']);
        exit;
    }
}

$messages = [];

if ($currentOrder) {
    $chatStmt = $pdo->prepare("SELECT * FROM qc_messages WHERE order_id = ? ORDER BY created_at ASC, id ASC");
    $chatStmt->execute([$currentOrder['id']]);
    $messages = $chatStmt->fetchAll();
}

$productName = $currentOrder['product_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lacak & QC Room - TitipGo</title>
    <link rel="stylesheet" href="css/buyer_feature_common.css">
    <link rel="stylesheet" href="css/qc_room.css">
</head>
<body>
<main class="app">
    <?php include 'sidebar_buyer.php'; ?>

    <section class="main">
        <header class="topbar">
            <h1><?= $totalOrders === 0 ? 'Lacak & QC Room' : 'Status Pesanan: ' . htmlspecialchars($productName); ?></h1>
            <div class="user-area">Hi, <?= htmlspecialchars($_SESSION['user']['name']); ?> <a class="user-icon" href="edit_profil.php">○</a></div>
        </header>

        <?php if ($totalOrders === 0): ?>
            <section class="page-content empty-state-wrap">
                <div class="empty-card card">
                    <div class="empty-icon">📦</div>
                    <h2>Anda belum memiliki pesanan yang sedang berjalan</h2>
                    <p>QC Room, tracking perjalanan, dan chat traveler akan aktif setelah pembayaran escrow berhasil dan traveler menerima pesanan Anda.</p>
                    <div class="empty-actions">
                        <a class="btn" href="buyer.php">Jelajahi Produk</a>
                        <a class="btn btn-outline" href="request_manual.php">Buat Request Manual</a>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <section class="page-content qc-layout">
                <div class="qc-card card">
                    <h2>💬 Real-Time Quality Control (QC)</h2>

                    <?php if ($errorInfo): ?>
                        <div class="alert error-alert"><?= htmlspecialchars($errorInfo); ?></div>
                    <?php endif; ?>

                    <div class="chat-list">
                        <?php if (empty($messages)): ?>
                            <div class="chat-bubble buyer-msg">Halo Mas, tolong kirimkan foto kondisi barang dan tanggal expired jika ada ya.</div>
                            <div class="proof-card">
                                <div class="photo-box">📷 Menunggu Foto QC</div>
                                <p>Traveler akan mengunggah foto bukti belanja, kondisi produk, atau struk melalui halaman Tugas SOP.</p>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($messages as $chat): ?>
                            <div class="chat-message <?= $chat['sender_role'] === 'buyer' ? 'buyer-message' : 'traveler-message'; ?>">
                                <div class="chat-role"><?= $chat['sender_role'] === 'buyer' ? 'Buyer' : 'Traveler'; ?></div>
                                <?php if (!empty($chat['message'])): ?>
                                    <p><?= nl2br(htmlspecialchars($chat['message'])); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($chat['photo_path'])): ?>
                                    <img class="chat-photo" src="<?= htmlspecialchars($chat['photo_path']); ?>" alt="Foto QC">
                                <?php endif; ?>
                                <small><?= htmlspecialchars($chat['created_at']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="confirm-box">
                        <h3>Konfirmasi Kualitas Barang</h3>
                        <a class="btn btn-red" href="qc_room.php?order_id=<?= $currentOrder['id']; ?>&confirm=reject">Tolak & Ganti</a>
                        <a class="btn" href="qc_room.php?order_id=<?= $currentOrder['id']; ?>&confirm=ok">Kualitas OK (Beli)</a>
                    </div>

                    <form class="reply-form" method="POST" enctype="multipart/form-data">
                        <input type="text" name="message" placeholder="Ketik balasan untuk traveler...">
                        <label class="photo-upload">📷<input type="file" name="photo" accept="image/*"></label>
                        <button type="submit">›</button>
                    </form>
                </div>

                <aside class="tracking-card card">
                    <h2>Human-Journey Tracking</h2>
                    <div class="escrow-status">💰 Status Escrow: Menunggu Konfirmasi<br><small>Traveler sedang belanja di toko.</small></div>
                    <ol class="timeline">
                        <li class="active"><b>Proses Pembelian Barang</b><span>Sedang mengecek kondisi barang via QC Room</span></li>
                        <li><b>Proses Packing (Tas Kabin)</b><span>Traveler akan mengunggah bukti kemasan paket</span></li>
                        <li><b>Menuju Bandara & Perjalanan</b><span>Mengudara dari kota asal menuju destinasi rute</span></li>
                        <li><b>Pemeriksaan Bea Cukai</b><span>Proses deklarasi pajak barang bawaan resmi</span></li>
                        <li><b>Diserahkan ke Kurir Domestik</b><span>Paket diserahkan ke logistik pilihan pembeli</span></li>
                        <li><b>Pesanan Diterima</b><span>Konfirmasi pesanan</span></li>
                    </ol>
                </aside>
            </section>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
