<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birthDate = trim($_POST['birth_date'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    $role = $_POST['role'] ?? 'buyer';

    if ($name === '') {
        $error = 'Nama lengkap tidak boleh kosong.';
    } elseif ($newPassword !== '' && $newPassword !== $confirmPassword) {
        $error = 'Konfirmasi password baru belum cocok.';
    } else {
        try {
            $columns = [];
            $values = [];

            $columns[] = 'name = ?';
            $values[] = $name;

            if (columnExists($pdo, 'users', 'phone')) {
                $columns[] = 'phone = ?';
                $values[] = $phone;
            }

            if (columnExists($pdo, 'users', 'email')) {
                $columns[] = 'email = ?';
                $values[] = $email;
            }

            if (columnExists($pdo, 'users', 'birth_date')) {
                $columns[] = 'birth_date = ?';
                $values[] = $birthDate;
            }

            if (columnExists($pdo, 'users', 'gender')) {
                $columns[] = 'gender = ?';
                $values[] = $gender;
            }

            if (columnExists($pdo, 'users', 'bio')) {
                $columns[] = 'bio = ?';
                $values[] = $bio;
            }

            if (columnExists($pdo, 'users', 'address')) {
                $columns[] = 'address = ?';
                $values[] = $address;
            }

            if (columnExists($pdo, 'users', 'role')) {
                $columns[] = 'role = ?';
                $values[] = $role;
            }

            if ($newPassword !== '') {
                $columns[] = 'password = ?';
                $values[] = $newPassword;
            }

            $values[] = $user['id'];

            $sql = "
                UPDATE users
                SET " . implode(', ', $columns) . "
                WHERE id = ?
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            $refresh = $pdo->prepare("
                SELECT *
                FROM users
                WHERE id = ?
            ");
            $refresh->execute([$user['id']]);

            $_SESSION['user'] = $refresh->fetch();
            $user = $_SESSION['user'];

            $message = 'Profil buyer berhasil disimpan.';
        } catch (PDOException $e) {
            $error = 'Gagal menyimpan profil. Pastikan tabel users sudah memiliki kolom yang sesuai.';
        }
    }
}

$username = strtolower(str_replace(' ', '_', $user['name']));
$emailValue = $user['email'] ?? '';
$phoneValue = $user['phone'] ?? '';
$birthValue = $user['birth_date'] ?? '';
$genderValue = $user['gender'] ?? '';
$bioValue = $user['bio'] ?? 'Suka menitip produk UMKM kuliner khas daerah dan koleksi terbatas.';
$addressValue = $user['address'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil Buyer - TitipGo</title>
    <link rel="stylesheet" href="css/edit_profil_buyer.css">
</head>
<body>
<main class="app">
    <?php include 'includes/sidebar_buyer.php'; ?>

    <section class="main">
        <header class="topbar">
            <h1>Pengaturan Akun & Keamanan Kata Sandi</h1>

            <a class="user-link" href="edit_profil_buyer.php">
                Hi, <?= htmlspecialchars($user['name']); ?>
                <span class="user-icon">◎</span>
            </a>
        </header>

        <section class="page-content">
            <div class="mode-bar">
                <div>
                    <h3>Mode Peran Aktif Anda</h3>
                    <p>Sesuaikan antarmuka platform sesuai kebutuhan transaksi saat ini.</p>
                </div>

                <div class="mode-buttons">
                    <a class="active" href="dashboard.php?role=buyer">🛍️ Mode Buyer</a>
                    <a href="dashboard.php?role=traveler">✈️ Mode Jastiper</a>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="notice-green"><?= htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="notice-red"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="profile-layout">
                <aside class="profile-card">
                    <div class="profile-photo-wrap">
                        <div class="profile-photo">👤</div>
                        <button class="photo-button" type="button">📷</button>
                    </div>

                    <h2><?= htmlspecialchars($user['name']); ?></h2>
                    <p class="username">@<?= htmlspecialchars($username); ?></p>

                    <div class="divider"></div>

                    <h3>Jaringan Teman & Relasi</h3>

                    <div class="friend-search">
                        🔍
                        <input placeholder="Cari username teman...">
                    </div>

                    <button class="friend-btn" type="button">
                        + Tambah Teman Baru
                    </button>
                </aside>

                <section class="form-card">
                    <h2>Informasi Profil & Kredensial</h2>

                    <form method="POST">
                        <input type="hidden" name="role" value="buyer">

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Username (@)</label>
                                <input value="<?= htmlspecialchars($username); ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label>Nama Lengkap Pengguna</label>
                                <input name="name" value="<?= htmlspecialchars($user['name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Alamat Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($emailValue); ?>" placeholder="nama@email.com">
                            </div>

                            <div class="form-group">
                                <label>Nomor Telepon</label>
                                <input name="phone" value="<?= htmlspecialchars($phoneValue); ?>" placeholder="08xxxxxxxxxx">
                            </div>

                            <div class="form-group">
                                <label>Tanggal Lahir</label>
                                <input name="birth_date" value="<?= htmlspecialchars($birthValue); ?>" placeholder="29 Februari 2069">
                            </div>

                            <div class="form-group">
                                <label>Jenis Kelamin</label>
                                <input name="gender" value="<?= htmlspecialchars($genderValue); ?>" placeholder="Perempuan / Laki-laki">
                            </div>

                            <div class="form-group full">
                                <label>Alamat Pengiriman Default</label>
                                <textarea name="address" placeholder="Masukkan alamat pengiriman utama"><?= htmlspecialchars($addressValue); ?></textarea>
                            </div>

                            <div class="form-group full">
                                <label>Bio / Deskripsi Profil</label>
                                <textarea name="bio"><?= htmlspecialchars($bioValue); ?></textarea>
                            </div>
                        </div>

                        <div class="security-section">
                            <h2>Keamanan Akun & Kata Sandi</h2>

                            <div class="password-row">
                                <div class="form-group">
                                    <label>Kata Sandi Akun</label>
                                    <input type="password" value="passworddummy" readonly>
                                </div>

                                <button class="change-password" type="button">
                                    🔑 Ganti Kata Sandi Baru
                                </button>
                            </div>

                            <div class="new-password-box">
                                <div class="form-group">
                                    <label>Masukkan Password Baru Anda</label>
                                    <input type="password" name="new_password" placeholder="SandiBaruUMKM2026!">
                                </div>

                                <div class="form-group">
                                    <label>Konfirmasi Password Baru</label>
                                    <input type="password" name="confirm_password" placeholder="SandiBaruUMKM2026!">
                                </div>

                                <span class="match-text">✓ Cocok</span>
                            </div>
                        </div>

                        <div class="button-row">
                            <a class="cancel-btn" href="buyer.php">Batalkan</a>
                            <button class="save-btn" type="submit">Simpan Profil</button>
                        </div>
                    </form>
                </section>
            </div>
        </section>
    </section>
</main>
</body>
</html>
