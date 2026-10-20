<?php
session_start();
require_once 'includes/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
    $stmt->execute([$_POST['email'], $_POST['password']]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user'] = $user;
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Email atau password salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TitipGo</title>
    <link rel="stylesheet" href="css/auth_login.css">
</head>
<body>
<div class="auth-wrap">
    <main class="auth-card">
        <section class="auth-left">
            <div class="brand">TitipGo</div>
            <div class="blob blob-top"></div>
            <div class="blob blob-bottom"></div>
            <h2 class="left-title">Selamat Datang Kembali!</h2>
            <p class="left-text">Lacak titipan pesananmu, pantau status QC secara langsung, dan temukan produk-produk trending hari ini.</p>
        </section>
        <section class="auth-right">
            <div class="form-box">
                <h1>Masuk ke Akun</h1>
                <p class="subtitle">Masukkan email dan kata sandi yang telah terdaftar.</p>
                <?php if($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <form method="POST">
                    <label class="label">Email Lengkap</label>
                    <input class="input" type="email" name="email" placeholder="Masukkan alamat email..." required>
                    <label class="label">Kata Sandi</label>
                    <input class="input" type="password" name="password" placeholder="Masukkan kata sandi..." required>
                    <div class="option-row">
                        <label class="remember"><input type="checkbox"> Ingat saya di perangkat ini</label>
                        <a href="#">Lupa Kata Sandi?</a>
                    </div>
                    <button class="btn" type="submit">Masuk</button>
                </form>
                <div class="divider">Atau masuk dengan</div>
                <button class="btn google" type="button">G&nbsp; Lanjutkan dengan Google</button>
                <p class="bottom-link">Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
                <p class="bottom-link">Demo: buyer@titipgo.com / 123456<br>traveler@titipgo.com / 123456</p>
            </div>
        </section>
    </main>
</div>
</body>
</html>
