<?php

declare(strict_types=1);

/**
 * BarbaBook - Página inicial: escolha da modalidade (Barbeiro ou Nails)
 * Domínio: tdesksolutions.com.br
 */

require_once __DIR__ . '/config/app.php';

$pageTitle = 'Escolha o serviço';
$modality   = null;

require_once __DIR__ . '/includes/header.php';

$base = SITE_BASE ?: '';
?>

<section class="hero choice-section">
  <div class="container">
    <h1 class="hero-title">BarbaBook</h1>
    <p class="hero-subtitle">Agendamento para barbeiros e nail design. Escolha a modalidade:</p>

    <div class="modality-cards">
      <a href="<?= $base ?>/barbeiro/estabelecimentos.php" class="modality-card modality-barbeiro">
        <span class="modality-icon" aria-hidden="true">✂️</span>
        <h2>Barbeiro</h2>
        <p>Cortes, barba e cuidados masculinos. Escolha a barbearia e agende.</p>
        <span class="modality-cta">Agendar →</span>
      </a>

      <a href="<?= $base ?>/nails/estabelecimentos.php" class="modality-card modality-nails">
        <span class="modality-icon" aria-hidden="true">💅</span>
        <h2>Nails</h2>
        <p>Unhas, alongamento e nail design. Escolha o espaço e agende.</p>
        <span class="modality-cta">Agendar →</span>
      </a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
