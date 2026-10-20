<?php
require_once 'includes/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'buyer';

    if (strlen(str_replace(' ', '', $name)) < 5) {
        $error = 'Nama harus memiliki minimal 5 huruf.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Password harus memiliki minimal 1 huruf kapital.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Password harus memiliki minimal 1 angka.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $password, $role, $phone]);
            $message = 'Registrasi berhasil. Silakan masuk ke akun Anda.';
        } catch (PDOException $e) {
            $error = 'Registrasi gagal. Email mungkin sudah digunakan.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - TitipGo</title>
    <link rel="stylesheet" href="css/auth_register.css">
</head>
<body>
<div class="auth-wrap">
    <main class="auth-card">
        <section class="auth-left">
            <div class="brand">TitipGo</div>
            <div class="blob blob-top"></div>
            <div class="blob blob-bottom"></div>
            <h2 class="left-title">Masuk, Cari Peluang Baru</h2>
            <p class="left-text">Belanja produk dari mana saja atau jadikan perjalananmu lebih bernilai.</p>
        </section>

        <section class="auth-right">
            <div class="form-box">
                <h1>Selamat Datang</h1>
                <p class="subtitle">Lengkapi data diri untuk mulai menitip pesanan dengan aman.</p>

                <?php if($message): ?>
                    <div class="alert"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="alert error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <label class="label">Nama Lengkap Sesuai Identitas</label>
                    <input class="input" type="text" name="name" placeholder="Misal: Jane Doe" required>

                    <label class="label">Alamat Email Aktif</label>
                    <input class="input" type="email" name="email" placeholder="jane.doe@example.com" required>

                    <input type="hidden" name="role" value="<?= htmlspecialchars($_GET['role'] ?? 'buyer') ?>">

                    <label class="label">No. HP</label>
                    <input class="input" type="text" name="phone" placeholder="08123456789">

                    <div class="password-row">
                        <label class="label">Kata Sandi</label>
                        <input class="input" type="password" name="password" placeholder="Minimal 1 huruf kapital dan 1 angka" required>
                        <span class="show-pass">LIHAT</span>
                    </div>

                    <label class="check-row">
                        <input type="checkbox" required>
                        <span>Saya menyetujui Ketentuan Layanan dan pemrosesan data pribadi sesuai Kebijakan Privasi.</span>
                    </label>

                    <button class="btn" type="submit">Daftar Akun Baru</button>
                </form>

                <p class="bottom-link">Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
            </div>
        </section>
    </main>
</div>
</body>
</html>
