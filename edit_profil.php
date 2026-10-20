<?php
require_once 'includes/traveler_auth.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'] ?? $_SESSION['user']['role'];

    if (!empty($_POST['new_password'])) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, password = ?, role = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $_POST['new_password'], $role, $_SESSION['user']['id']]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, role = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $role, $_SESSION['user']['id']]);
    }

    $refresh = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $refresh->execute([$_SESSION['user']['id']]);
    $_SESSION['user'] = $refresh->fetch();
    $user = $_SESSION['user'];
    $message = 'Profil berhasil disimpan.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - TitipGo</title>
    <link rel="stylesheet" href="css/edit_profil.css">
</head>
<body>
<main class="app">
    <?php include 'includes/traveler_sidebar.php'; ?>
    <section class="main">
        <header class="topbar"><h1>Pengaturan Akun & Keamanan Kata Sandi</h1><a class="user-link" href="edit_profil.php">Hi! <?= htmlspecialchars($user['name']); ?> <span class="user-icon">◎</span></a></header>
        <div class="page-content">
            <div class="mode-bar">
                <div><h3>Mode Peran Aktif Anda</h3><p class="muted">Sesuaikan antarmuka platform sesuai kebutuhan transaksi saat ini.</p></div>
                <div class="mode-buttons"><a href="dashboard.php?role=buyer">🛍️ Mode Buyer</a><a class="active" href="dashboard.php?role=traveler">✈️ Mode Jastiper</a></div>
            </div>
            <?php if ($message): ?><div class="notice-green"><?= htmlspecialchars($message); ?></div><?php endif; ?>
            <div class="profile-layout">
                <aside class="card" style="text-align:center;">
                    <div class="profile-photo">👤</div>
                    <h2><?= htmlspecialchars($user['name']); ?></h2>
                    <p class="muted">@<?= strtolower(str_replace(' ', '_', htmlspecialchars($user['name']))); ?></p>
                    <br><hr><br>
                    <h3>Jaringan Teman & Relasi</h3><br>
                    <input class="input" placeholder="Cari username teman...">
                    <button class="btn btn-light btn-full">+ Tambah Teman Baru</button>
                </aside>
                <section class="card">
                    <h2 class="section-title">Informasi Profil & Kredensial</h2>
                    <form method="POST">
                        <div class="form-grid">
                            <div><label>Username</label><input class="input" value="<?= strtolower(str_replace(' ', '_', htmlspecialchars($user['name']))); ?>" readonly></div>
                            <div><label>Nama Lengkap Pengguna</label><input class="input" name="name" value="<?= htmlspecialchars($user['name']); ?>" required></div>
                            <div><label>No. HP</label><input class="input" name="phone" value="<?= htmlspecialchars($user['phone'] ?? ''); ?>"></div>
                            <div><label>Role Aktif</label><select class="select" name="role"><option value="buyer">Buyer</option><option value="traveler" selected>Traveler</option></select></div>
                            <div class="full"><label>Bio / Deskripsi Profil</label><textarea class="textarea">Suka membantu pembeli menemukan produk lokal dan internasional.</textarea></div>
                        </div>
                        <h2 class="section-title">Keamanan Akun & Kata Sandi</h2>
                        <input class="input" type="password" name="new_password" placeholder="Masukkan password baru jika ingin mengganti">
                        <button class="btn" type="submit">Simpan Profil</button>
                    </form>
                </section>
            </div>
        </div>
    </section>
</main>
</body>
</html>
