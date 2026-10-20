<?php
$currentPage = $currentPage ?? '';
$pageTitle = $pageTitle ?? 'TitipGo';
$pageCss = $pageCss ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle); ?></title>
  <link rel="stylesheet" href="css/common.css">
  <?php if ($pageCss): ?><link rel="stylesheet" href="css/<?= htmlspecialchars($pageCss); ?>"><?php endif; ?>
</head>
<body>
<header class="navbar">
  <a href="index.php" class="logo">TitipGo</a>
  <nav class="nav-links">
    <a class="<?= $currentPage==='beranda'?'active':''; ?>" href="index.php">Beranda</a>
    <a class="<?= $currentPage==='buyer-flow'?'active':''; ?>" href="cara_kerja_buyer.php">Cara Kerja</a>
    <a class="<?= $currentPage==='produk'?'active':''; ?>" href="eksplor_produk.php">Eksplor Produk</a>
    <a class="<?= $currentPage==='keamanan'?'active':''; ?>" href="keamanan.php">Keamanan</a>
  </nav>
  <div class="nav-actions">
    <a href="login.php">Masuk</a>
    <a href="register.php" class="btn btn-primary">Daftar Akun</a>
  </div>
</header>
