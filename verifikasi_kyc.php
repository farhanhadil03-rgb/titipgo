<?php
require_once 'includes/traveler_auth.php';

$travelerId = $user['id'];
$successInfo = '';
$errorInfo = '';

function uploadKycFile($file, $prefix)
{
    if (empty($file['name'])) {
        return null;
    }

    $uploadDir = __DIR__ . '/uploads/kyc/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions)) {
        return false;
    }

    $fileName = $prefix . '_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'uploads/kyc/' . $fileName;
    }

    return null;
}

function getLatestKyc($pdo, $travelerId)
{
    try {
        $stmt = $pdo->prepare("
            SELECT *
            FROM traveler_kyc
            WHERE user_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$travelerId]);

        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ktpNumber = trim($_POST['ktp_number'] ?? '');
    $passportNumber = trim($_POST['passport_number'] ?? '');
    $bankName = trim($_POST['bank_name'] ?? '');
    $accountNumber = trim($_POST['account_number'] ?? '');
    $accountHolder = trim($_POST['account_holder'] ?? '');
    $depositAmount = (float) ($_POST['deposit_amount'] ?? 0);

    $ktpImage = uploadKycFile($_FILES['ktp_image'] ?? [], 'ktp');
    $passportImage = uploadKycFile($_FILES['passport_image'] ?? [], 'passport');
    $selfieImage = uploadKycFile($_FILES['selfie_image'] ?? [], 'selfie');

    if ($ktpImage === false || $passportImage === false || $selfieImage === false) {
        $errorInfo = 'Format dokumen harus JPG, JPEG, PNG, WEBP, atau PDF.';
    } elseif ($ktpNumber === '' || $bankName === '' || $accountNumber === '' || $accountHolder === '') {
        $errorInfo = 'KTP, rekening bank, nomor rekening, dan nama pemilik rekening wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO traveler_kyc
                (
                    user_id,
                    ktp_number,
                    passport_number,
                    ktp_image,
                    passport_image,
                    selfie_image,
                    bank_name,
                    account_number,
                    account_holder,
                    deposit_amount,
                    verification_status,
                    badge_status,
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
                    ?,
                    ?,
                    ?,
                    ?,
                    'pending',
                    'Under Review',
                    NOW()
                )
            ");

            $stmt->execute([
                $travelerId,
                $ktpNumber,
                $passportNumber,
                $ktpImage,
                $passportImage,
                $selfieImage,
                $bankName,
                $accountNumber,
                $accountHolder,
                $depositAmount
            ]);

            $successInfo = 'Data KYC berhasil dikirim. Status akun sedang menunggu verifikasi admin.';
        } catch (PDOException $e) {
            $errorInfo = 'Gagal menyimpan data KYC. Import database/verifikasi_kyc_update.sql terlebih dahulu.';
        }
    }
}

$currentKyc = getLatestKyc($pdo, $travelerId);

$kycStatus = $currentKyc['verification_status'] ?? 'belum lengkap';
$badgeStatus = $currentKyc['badge_status'] ?? 'Belum Verified';
$hasKtp = !empty($currentKyc['ktp_image']) || !empty($currentKyc['ktp_number']);
$hasSelfie = !empty($currentKyc['selfie_image']);
$hasBank = !empty($currentKyc['bank_name']) && !empty($currentKyc['account_number']);
$hasDeposit = !empty($currentKyc['deposit_amount']) && (float) $currentKyc['deposit_amount'] > 0;
$isVerified = $kycStatus === 'approved';

function stepClass($condition)
{
    return $condition ? 'done' : '';
}

function stepNumber($condition, $number)
{
    return $condition ? '✓' : $number;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi KYC - TitipGo</title>
    <link rel="stylesheet" href="css/verifikasi_kyc.css">
</head>
<body>
<main class="app">
    <?php include 'includes/traveler_sidebar.php'; ?>

    <section class="main">
        <header class="topbar">
            <h1>Pusat Kepercayaan (KYC & Deposit)</h1>
            <a class="user-link" href="edit_profil.php">
                Hi! Jastiper
                <span class="user-icon">◎</span>
            </a>
        </header>

        <section class="page-content kyc-layout">
            <section class="card kyc-main-card">
                <p class="page-label">KNOW YOUR CUSTOMER</p>
                <h2>Selesaikan Verifikasi Traveler</h2>
                <p class="intro-text">
                    KYC digunakan untuk memastikan traveler valid, mengurangi penipuan, meningkatkan keamanan buyer, dan menjaga kualitas platform.
                </p>

                <?php if ($successInfo): ?>
                    <div class="notice-green"><?= htmlspecialchars($successInfo); ?></div>
                <?php endif; ?>

                <?php if ($errorInfo): ?>
                    <div class="notice-red"><?= htmlspecialchars($errorInfo); ?></div>
                <?php endif; ?>

                <div class="status-banner <?= $isVerified ? 'verified-status' : ''; ?>">
                    <div>
                        <b>Status Akun Traveler</b>
                        <p><?= htmlspecialchars(ucfirst($kycStatus)); ?> • Badge: <?= htmlspecialchars($badgeStatus); ?></p>
                    </div>

                    <span><?= $isVerified ? 'Verified' : 'Review'; ?></span>
                </div>

                <div class="kyc-step <?= stepClass($hasKtp); ?>">
                    <span class="step-number <?= stepClass($hasKtp); ?>"><?= stepNumber($hasKtp, 1); ?></span>
                    <div>
                        <h3>Upload Dokumen Identitas</h3>
                        <p class="muted">Upload KTP asli. Paspor bersifat opsional untuk perjalanan internasional.</p>
                    </div>
                    <b><?= $hasKtp ? 'Tuntas' : 'Belum'; ?></b>
                </div>

                <div class="kyc-step <?= stepClass($hasSelfie); ?>">
                    <span class="step-number <?= stepClass($hasSelfie); ?>"><?= stepNumber($hasSelfie, 2); ?></span>
                    <div>
                        <h3>Selfie Verification</h3>
                        <p class="muted">Unggah selfie untuk simulasi verifikasi wajah dan pencocokan identitas.</p>
                    </div>
                    <b><?= $hasSelfie ? 'Tuntas' : 'Belum'; ?></b>
                </div>

                <div class="kyc-step <?= stepClass($hasBank); ?>">
                    <span class="step-number <?= stepClass($hasBank); ?>"><?= stepNumber($hasBank, 3); ?></span>
                    <div>
                        <h3>Verifikasi Rekening Bank</h3>
                        <p class="muted">Tambahkan rekening bank dan nama pemilik rekening untuk pencairan escrow.</p>
                    </div>
                    <b><?= $hasBank ? 'Tuntas' : 'Belum'; ?></b>
                </div>

                <div class="kyc-step <?= stepClass($hasDeposit); ?>">
                    <span class="step-number <?= stepClass($hasDeposit); ?>"><?= stepNumber($hasDeposit, 4); ?></span>
                    <div>
                        <h3>Deposit Jaminan</h3>
                        <p class="muted">Setorkan deposit sebagai komitmen dan perlindungan transaksi.</p>
                    </div>
                    <b><?= $hasDeposit ? 'Tuntas' : 'Belum'; ?></b>
                </div>

                <form class="kyc-form" method="POST" enctype="multipart/form-data">
                    <h2 class="section-title">Lengkapi Data KYC</h2>

                    <div class="form-grid">
                        <div>
                            <label>Nomor KTP</label>
                            <input class="input" name="ktp_number" placeholder="Masukkan nomor KTP" required>
                        </div>

                        <div>
                            <label>Nomor Paspor (Opsional)</label>
                            <input class="input" name="passport_number" placeholder="Untuk perjalanan internasional">
                        </div>

                        <div>
                            <label>Upload KTP</label>
                            <input class="input" type="file" name="ktp_image" accept="image/*,.pdf">
                        </div>

                        <div>
                            <label>Upload Paspor</label>
                            <input class="input" type="file" name="passport_image" accept="image/*,.pdf">
                        </div>

                        <div>
                            <label>Upload Selfie</label>
                            <input class="input" type="file" name="selfie_image" accept="image/*">
                        </div>

                        <div>
                            <label>Nominal Deposit Jaminan</label>
                            <input class="input" type="number" name="deposit_amount" placeholder="200000">
                        </div>

                        <div>
                            <label>Nama Bank</label>
                            <input class="input" name="bank_name" placeholder="Contoh: Bank BCA" required>
                        </div>

                        <div>
                            <label>Nomor Rekening</label>
                            <input class="input" name="account_number" placeholder="Masukkan nomor rekening" required>
                        </div>

                        <div class="full">
                            <label>Nama Pemilik Rekening</label>
                            <input class="input" name="account_holder" placeholder="Nama sesuai rekening" required>
                        </div>
                    </div>

                    <button class="btn btn-full" type="submit">
                        Kirim Data Verifikasi
                    </button>
                </form>
            </section>

            <aside class="side-panel">
                <section class="card dark-card">
                    <h2 class="section-title">Keuntungan Naik Level</h2>

                    <div class="gold-box">
                        🏆 Gold Badge Traveler
                        <br><br>
                        ✓ Muncul paling atas di filter pencarian buyer
                        <br>
                        ✓ Batas transaksi escrow lebih tinggi
                        <br>
                        ✓ Badge “Sangat Terpercaya” di profil publik
                    </div>

                    <a class="btn btn-full" href="#kyc-form-area">
                        Lengkapi Data Sekarang
                    </a>
                </section>

                <section class="card benefit-card">
                    <h2>Mendapatkan Badge Verifikasi</h2>
                    <p>
                        Setelah KYC disetujui, akun akan mendapatkan badge Verified sehingga lebih mudah dipercaya buyer dan peluang menerima order lebih besar.
                    </p>
                </section>
            </aside>
        </section>
    </section>
</main>
</body>
</html>
