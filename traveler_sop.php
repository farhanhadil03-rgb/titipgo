<?php
require_once 'includes/traveler_auth.php';

$travelerId = $user['id'];
$errorInfo = '';
$successInfo = '';

$orderStmt = $pdo->prepare("
    SELECT *
    FROM orders
    WHERE traveler_id = ?
    AND status NOT IN ('completed', 'cancelled')
    ORDER BY id DESC
    LIMIT 1
");
$orderStmt->execute([$travelerId]);
$currentOrder = $orderStmt->fetch();

function safeColumnExists($pdo, $table, $column)
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

function safeTableExists($pdo, $table)
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

function auditLog($pdo, $orderId, $travelerId, $stepCode, $stepTitle, $note, $photoPath = null)
{
    if (!safeTableExists($pdo, 'sop_audit_logs')) {
        return;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO sop_audit_logs
            (
                order_id,
                traveler_id,
                step_code,
                step_title,
                note,
                photo_path,
                created_at
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                NOW()
            )
        ");

        $stmt->execute([
            $orderId,
            $travelerId,
            $stepCode,
            $stepTitle,
            $note,
            $photoPath
        ]);
    } catch (PDOException $e) {
        return;
    }
}

if ($currentOrder && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'send_chat') {
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
                $fileName = 'traveler_qc_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                    $photoPath = 'uploads/qc/' . $fileName;
                }
            } else {
                $errorInfo = 'Format foto harus JPG, JPEG, PNG, atau WEBP.';
            }
        }

        if ($chatMessage !== '' || $photoPath !== null) {
            $insertChat = $pdo->prepare("
                INSERT INTO qc_messages
                (
                    order_id,
                    sender_id,
                    sender_role,
                    message,
                    photo_path
                )
                VALUES
                (
                    ?,
                    ?,
                    'traveler',
                    ?,
                    ?
                )
            ");
            $insertChat->execute([
                $currentOrder['id'],
                $travelerId,
                $chatMessage,
                $photoPath
            ]);

            auditLog(
                $pdo,
                $currentOrder['id'],
                $travelerId,
                'chat_qc',
                'Chat dan Dokumentasi QC',
                $chatMessage !== '' ? $chatMessage : 'Traveler mengunggah foto QC.',
                $photoPath
            );

            if ($currentOrder['status'] === 'traveler_found' || $currentOrder['status'] === 'request_created') {
                $update = $pdo->prepare("
                    UPDATE orders
                    SET status = 'qc_in_progress'
                    WHERE id = ?
                ");
                $update->execute([$currentOrder['id']]);
            }

            header('Location: traveler_sop.php');
            exit;
        }
    }

    if ($formAction === 'update_sop') {
        $stepCode = $_POST['step_code'] ?? '';
        $stepTitle = $_POST['step_title'] ?? '';
        $note = trim($_POST['audit_note'] ?? '');
        $receiptNumber = trim($_POST['receipt_number'] ?? '');
        $photoPath = null;

        if (!empty($_FILES['sop_photo']['name'])) {
            $uploadDir = __DIR__ . '/uploads/sop/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $extension = strtolower(pathinfo($_FILES['sop_photo']['name'], PATHINFO_EXTENSION));

            if (in_array($extension, $allowedExtensions)) {
                $fileName = 'sop_' . $stepCode . '_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['sop_photo']['tmp_name'], $targetPath)) {
                    $photoPath = 'uploads/sop/' . $fileName;
                }
            } else {
                $errorInfo = 'Format foto SOP harus JPG, JPEG, PNG, atau WEBP.';
            }
        }

        if ($stepCode !== '') {
            if (safeTableExists($pdo, 'sop_audit_logs')) {
                auditLog(
                    $pdo,
                    $currentOrder['id'],
                    $travelerId,
                    $stepCode,
                    $stepTitle,
                    $note !== '' ? $note : 'Tahap SOP ditandai selesai oleh traveler.',
                    $photoPath
                );
            }

            if ($stepCode === 'buy_item') {
                $newStatus = 'item_purchased';
            } elseif ($stepCode === 'verify_item') {
                $newStatus = 'item_verified';
            } elseif ($stepCode === 'quality_control') {
                $newStatus = 'qc_in_progress';
            } elseif ($stepCode === 'delivery_ready') {
                $newStatus = 'on_delivery';
            } else {
                $newStatus = $currentOrder['status'];
            }

            $updateOrder = $pdo->prepare("
                UPDATE orders
                SET status = ?, note = CONCAT(COALESCE(note, ''), ?)
                WHERE id = ?
            ");

            $extraNote = $receiptNumber !== '' ? "\nNomor resi: " . $receiptNumber : '';
            $updateOrder->execute([
                $newStatus,
                $extraNote,
                $currentOrder['id']
            ]);

            $successInfo = 'Tahap SOP berhasil diperbarui dan tercatat untuk audit internal.';
            header('Location: traveler_sop.php?success=1');
            exit;
        }
    }
}

if (isset($_GET['success'])) {
    $successInfo = 'Tahap SOP berhasil diperbarui dan tercatat untuk audit internal.';
}

$messages = [];

if ($currentOrder) {
    $chatStmt = $pdo->prepare("
        SELECT *
        FROM qc_messages
        WHERE order_id = ?
        ORDER BY created_at ASC, id ASC
    ");
    $chatStmt->execute([$currentOrder['id']]);
    $messages = $chatStmt->fetchAll();
}

$auditLogs = [];

if ($currentOrder && safeTableExists($pdo, 'sop_audit_logs')) {
    $auditStmt = $pdo->prepare("
        SELECT *
        FROM sop_audit_logs
        WHERE order_id = ?
        ORDER BY created_at DESC, id DESC
        LIMIT 8
    ");
    $auditStmt->execute([$currentOrder['id']]);
    $auditLogs = $auditStmt->fetchAll();
}

$currentStatus = $currentOrder['status'] ?? '';

$sopSteps = [
    [
        'code' => 'buy_item',
        'title' => 'Tahap 1 – Membeli Barang',
        'description' => 'Membeli barang sesuai pesanan buyer dan mengunggah bukti struk pembelian.',
        'status_match' => ['item_purchased', 'item_verified', 'qc_in_progress', 'on_delivery', 'completed'],
        'upload_label' => 'Upload struk pembelian',
        'note_placeholder' => 'Contoh: Barang sudah dibeli sesuai pesanan buyer.'
    ],
    [
        'code' => 'verify_item',
        'title' => 'Tahap 2 – Verifikasi Barang',
        'description' => 'Mengambil foto barang dan memastikan warna, ukuran, serta spesifikasi sesuai.',
        'status_match' => ['item_verified', 'qc_in_progress', 'on_delivery', 'completed'],
        'upload_label' => 'Upload foto produk',
        'note_placeholder' => 'Contoh: Warna dan ukuran sudah sesuai.'
    ],
    [
        'code' => 'quality_control',
        'title' => 'Tahap 3 – Quality Control (QC)',
        'description' => 'Mengunggah dokumentasi visual kondisi barang dan kemasan barang.',
        'status_match' => ['qc_in_progress', 'on_delivery', 'completed'],
        'upload_label' => 'Upload foto QC dan kemasan',
        'note_placeholder' => 'Contoh: Barang aman, kemasan rapi, tidak ada kerusakan.'
    ],
    [
        'code' => 'delivery_ready',
        'title' => 'Tahap 4 – Pengiriman',
        'description' => 'Menandai barang siap dikirim dan menginput nomor resi kurir domestik.',
        'status_match' => ['on_delivery', 'completed'],
        'upload_label' => 'Upload bukti serah kurir',
        'note_placeholder' => 'Contoh: Barang sudah diserahkan ke kurir domestik.'
    ]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tugas SOP & Audit - TitipGo</title>
    <link rel="stylesheet" href="css/traveler_sop.css">
</head>
<body>
<main class="app">
    <?php include 'includes/traveler_sidebar.php'; ?>

    <section class="main">
        <header class="topbar">
            <h1>Tugas SOP & Audit</h1>
            <a class="user-link" href="edit_profil.php">
                <?= htmlspecialchars($user['name']); ?>
                <span class="user-icon">◎</span>
            </a>
        </header>

        <?php if (!$currentOrder): ?>
            <div class="page-content">
                <div class="card empty-sop-card">
                    <div class="empty-icon">📋</div>
                    <h2>Belum ada Tugas SOP aktif</h2>
                    <p>Tugas validasi akan muncul setelah traveler mengambil job dari Dashboard & Misi.</p>
                    <a class="btn" href="traveler.php">Kembali ke Dashboard & Misi</a>
                </div>
            </div>
        <?php else: ?>
            <div class="page-content sop-audit-layout">
                <section class="card sop-card">
                    <div class="sop-header">
                        <div>
                            <p class="page-label">STANDARD OPERATING PROCEDURE</p>
                            <h2>Checklist SOP Traveler</h2>
                            <p>Pastikan setiap tahapan diselesaikan agar kualitas layanan tetap terjaga dan data audit tersimpan.</p>
                        </div>

                        <div class="order-badge">
                            #ORD-<?= str_pad($currentOrder['id'], 3, '0', STR_PAD_LEFT); ?>
                        </div>
                    </div>

                    <?php if ($errorInfo): ?>
                        <div class="notice-red"><?= htmlspecialchars($errorInfo); ?></div>
                    <?php endif; ?>

                    <?php if ($successInfo): ?>
                        <div class="notice-green"><?= htmlspecialchars($successInfo); ?></div>
                    <?php endif; ?>

                    <div class="product-summary">
                        <b><?= htmlspecialchars($currentOrder['product_name']); ?></b>
                        <span>
                            <?= htmlspecialchars($currentOrder['product_country']); ?>
                            • Status:
                            <?= htmlspecialchars($currentOrder['status']); ?>
                        </span>
                    </div>

                    <div class="sop-step-list">
                        <?php foreach ($sopSteps as $index => $step): ?>
                            <?php $isDone = in_array($currentStatus, $step['status_match']); ?>
                            <form class="sop-step <?= $isDone ? 'done' : ''; ?>" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="form_action" value="update_sop">
                                <input type="hidden" name="step_code" value="<?= htmlspecialchars($step['code']); ?>">
                                <input type="hidden" name="step_title" value="<?= htmlspecialchars($step['title']); ?>">

                                <div class="step-number">
                                    <?= $isDone ? '✓' : ($index + 1); ?>
                                </div>

                                <div class="step-content">
                                    <h3><?= htmlspecialchars($step['title']); ?></h3>
                                    <p><?= htmlspecialchars($step['description']); ?></p>

                                    <div class="step-form-grid">
                                        <label class="sop-upload">
                                            <input type="file" name="sop_photo" accept="image/*">
                                            📷 <?= htmlspecialchars($step['upload_label']); ?>
                                        </label>

                                        <?php if ($step['code'] === 'delivery_ready'): ?>
                                            <input class="sop-input" type="text" name="receipt_number" placeholder="Nomor resi kurir domestik">
                                        <?php endif; ?>

                                        <textarea name="audit_note" placeholder="<?= htmlspecialchars($step['note_placeholder']); ?>"></textarea>
                                    </div>
                                </div>

                                <button class="btn btn-green" type="submit">
                                    <?= $isDone ? 'Update Bukti' : 'Tandai Selesai'; ?>
                                </button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                </section>

                <aside class="card audit-card">
                    <h2 class="section-title">Audit Internal</h2>
                    <p class="audit-desc">Sistem mencatat waktu pengerjaan, bukti foto, dan status setiap tahapan untuk kebutuhan sengketa.</p>

                    <?php if (empty($auditLogs)): ?>
                        <div class="empty-audit">Belum ada catatan audit untuk pesanan ini.</div>
                    <?php endif; ?>

                    <?php foreach ($auditLogs as $audit): ?>
                        <div class="audit-log">
                            <b><?= htmlspecialchars($audit['step_title']); ?></b>
                            <p><?= htmlspecialchars($audit['note']); ?></p>

                            <?php if (!empty($audit['photo_path'])): ?>
                                <a href="<?= htmlspecialchars($audit['photo_path']); ?>" target="_blank">Lihat bukti foto</a>
                            <?php endif; ?>

                            <small><?= htmlspecialchars($audit['created_at']); ?></small>
                        </div>
                    <?php endforeach; ?>
                </aside>
            </div>

            <div class="page-content chat-audit-layout">
                <section class="card chat-card">
                    <h2 class="section-title">💬 Chat Buyer & Traveler</h2>
                    <p class="muted">Gunakan area ini untuk memberi update, mengirim foto QC, dan menjawab pertanyaan buyer.</p>

                    <div class="chat-list">
                        <?php if (empty($messages)): ?>
                            <div class="chat-bubble">
                                Belum ada pesan QC. Kirim foto produk atau struk belanja agar buyer bisa mengecek kondisi barang.
                            </div>
                        <?php endif; ?>

                        <?php foreach ($messages as $chat): ?>
                            <div class="chat-message <?= $chat['sender_role'] === 'traveler' ? 'traveler-message' : 'buyer-message'; ?>">
                                <div class="chat-role">
                                    <?= $chat['sender_role'] === 'traveler' ? 'Traveler' : 'Buyer'; ?>
                                </div>

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

                    <form class="sop-form" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="form_action" value="send_chat">

                        <label class="upload-box">
                            <input type="file" name="photo" accept="image/*">
                            <div>
                                <div class="upload-icon">📷</div>
                                <b>Klik untuk Ambil / Unggah Foto Struk & Produk</b>
                                <p>Format JPG, PNG, atau WEBP maksimal 5MB</p>
                            </div>
                        </label>

                        <div class="message-input">
                            <input name="message" placeholder="Ketik pesan balasan untuk pembeli...">
                            <button class="btn btn-green" type="submit">›</button>
                        </div>
                    </form>
                </section>

                <aside class="card">
                    <h2 class="section-title">Traveler-Journey Tracking</h2>
                    <div class="notice-green">
                        🔒 Dana Escrow Terjamin Aman
                        <br>
                        <span class="muted">Uang belanja buyer telah dikunci penuh di platform.</span>
                    </div>

                    <div class="timeline">
                        <div class="timeline-item <?= in_array($currentStatus, ['traveler_found', 'item_purchased', 'item_verified', 'qc_in_progress', 'on_delivery', 'completed']) ? 'active' : ''; ?>">
                            <h4>1. Pembelian & Validasi Toko</h4>
                            <p>Sedang membelikan barang pesanan di store.</p>
                        </div>

                        <div class="timeline-item <?= in_array($currentStatus, ['item_purchased', 'item_verified', 'qc_in_progress', 'on_delivery', 'completed']) ? 'active' : ''; ?>">
                            <h4>2. Unggah Foto Packing Barang</h4>
                            <p>Menata produk rapi dan mengunggah bukti visual.</p>
                        </div>

                        <div class="timeline-item <?= in_array($currentStatus, ['qc_in_progress', 'on_delivery', 'completed']) ? 'active' : ''; ?>">
                            <h4>3. Quality Control</h4>
                            <p>Buyer mengecek kondisi barang melalui QC Room.</p>
                        </div>

                        <div class="timeline-item <?= in_array($currentStatus, ['on_delivery', 'completed']) ? 'active' : ''; ?>">
                            <h4>4. Pengiriman</h4>
                            <p>Barang diserahkan ke kurir domestik atau COD.</p>
                        </div>

                        <div class="timeline-item <?= $currentStatus === 'completed' ? 'active' : ''; ?>">
                            <h4>5. Selesai & Dana Cair</h4>
                            <p>Pencairan uang pokok belanja beserta komisi jasa.</p>
                        </div>
                    </div>
                </aside>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
