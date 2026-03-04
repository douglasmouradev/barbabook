<?php
if (!defined('SITE_BASE')) require_once __DIR__ . '/../config/app.php';
if (!isset($pageTitle)) $pageTitle = 'BarbaBook';
if (!isset($modality)) $modality = null;
$base = SITE_BASE ?: '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> | BarbaBook - tdesksolutions.com.br</title>
  <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="<?= $modality ? "modality-{$modality}" : '' ?>">
  <header class="site-header">
    <div class="container header-inner">
      <a href="<?= $base ?>/" class="logo">BarbaBook</a>
      <nav class="nav-main">
        <a href="<?= $base ?>/">Início</a>
        <?php if ($modality === 'barbeiro'): ?>
          <a href="<?= $base ?>/barbeiro/agendamentos.php">Agendamentos Barbeiro</a>
        <?php elseif ($modality === 'nails'): ?>
          <a href="<?= $base ?>/nails/agendamentos.php">Agendamentos Nails</a>
        <?php else: ?>
          <a href="<?= $base ?>/barbeiro/agendamentos.php">Barbeiro</a>
          <a href="<?= $base ?>/nails/agendamentos.php">Nails</a>
        <?php endif; ?>
        <a href="<?= $base ?>/admin/login.php" class="nav-login">Login</a>
      </nav>
    </div>
  </header>
  <main class="main-content">
