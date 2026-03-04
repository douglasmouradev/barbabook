<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';

$pageTitle = 'Escolha o espaço de nails';
$modality  = 'nails';

$estabelecimentos = [];
try {
    $tabelaExiste = (bool) $pdo->query("SHOW TABLES LIKE 'estabelecimentos'")->fetch();
    if ($tabelaExiste) {
        $stmt = $pdo->prepare("SELECT id, nome, slug FROM estabelecimentos WHERE tipo = 'nails' AND ativo = 1 ORDER BY nome");
        $stmt->execute();
        $estabelecimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    // ignora
}

$base = SITE_BASE ?: '';
if (empty($estabelecimentos)) {
    header('Location: ' . $base . '/nails/agendamentos.php');
    exit;
}

if (count($estabelecimentos) === 1) {
    header('Location: ' . $base . '/nails/agendamentos.php?e=' . rawurlencode($estabelecimentos[0]['slug']));
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="hero choice-section">
  <div class="container">
    <h1 class="page-title">Nails</h1>
    <p class="page-desc">Escolha o espaço para agendar:</p>
    <ul class="agendamentos-list" style="max-width:400px;margin:1rem 0;">
      <?php foreach ($estabelecimentos as $e): ?>
        <li class="agendamento-item">
          <a href="<?= $base ?>/nails/agendamentos.php?e=<?= rawurlencode($e['slug']) ?>"><?= htmlspecialchars($e['nome']) ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
