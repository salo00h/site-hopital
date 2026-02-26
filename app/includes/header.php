<?php
$user = $_SESSION['user'] ?? null;
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Fil Rouge - SI Hopital</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="page">

<header class="topbar">
  <div class="brand">SI Hôpital</div>

  <div class="right">
    <?php if ($user): ?>
      <div class="user">
        <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>
        (<?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?>)
      </div>
      <a class="btn" href="index.php?action=logout">Logout</a>
    <?php endif; ?>
  </div>
</header>

<div class="content">