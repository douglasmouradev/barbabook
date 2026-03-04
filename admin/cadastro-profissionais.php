<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';

admin_require_login();

if (admin_multitenant_ativo() && admin_estabelecimento_efetivo_id() === null) {
    header('Location: ' . (SITE_BASE ?: '') . '/admin/cadastro-estabelecimentos.php');
    exit;
}

$pageTitle = 'Cadastro de Profissionais';
$modality  = null;
$estabelecimento_id = admin_multitenant_ativo() ? admin_estabelecimento_efetivo_id() : null;
$tipo_estab = admin_multitenant_ativo() ? admin_estabelecimento_efetivo_tipo() : null;

$erro    = null;
$sucesso = null;

$modalidade_id = null;
if ($tipo_estab) {
    $m = $pdo->prepare("SELECT id FROM modalidades WHERE slug = ? LIMIT 1");
    $m->execute([$tipo_estab]);
    $modalidade_id = (int) $m->fetchColumn();
}

$sqlProf = "
    SELECT p.id, p.nome, p.ativo, m.nome AS modalidade_nome
    FROM profissionais p
    JOIN modalidades m ON m.id = p.modalidade_id
";
$params = [];
if ($estabelecimento_id !== null) {
    $sqlProf .= " WHERE p.estabelecimento_id = ?";
    $params[] = $estabelecimento_id;
}
$sqlProf .= " ORDER BY p.nome";
$stmt = $pdo->prepare($sqlProf);
$stmt->execute($params);
$profissionais = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_profissional'])) {
    $nome = trim((string) ($_POST['nome'] ?? ''));
    if ($nome === '') {
        $erro = 'Informe o nome do profissional.';
    } elseif ($modalidade_id === null && $estabelecimento_id !== null) {
        $erro = 'Estabelecimento sem modalidade definida.';
    } else {
        try {
            if ($estabelecimento_id !== null && $modalidade_id !== null) {
                $pdo->prepare("INSERT INTO profissionais (modalidade_id, estabelecimento_id, nome, ativo) VALUES (?, ?, ?, 1)")
                    ->execute([$modalidade_id, $estabelecimento_id, $nome]);
            } else {
                $mid = $pdo->query("SELECT id FROM modalidades WHERE slug = 'barbeiro' LIMIT 1")->fetchColumn();
                $pdo->prepare("INSERT INTO profissionais (modalidade_id, nome, ativo) VALUES (?, ?, 1)")->execute([$mid, $nome]);
            }
            $sucesso = 'Profissional cadastrado.';
            $stmt->execute($params);
            $profissionais = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $erro = 'Erro ao salvar. Tente novamente.';
        }
    }
}

require __DIR__ . '/../includes/header.php';
$base = SITE_BASE ?: '';
$adminPage = 'profissionais';
require __DIR__ . '/../includes/admin-sidebar.php';
?>

<section class="agendamentos-page">
  <div class="container">
    <h1 class="page-title">Cadastro de Profissionais</h1>
    <p class="page-desc">Cadastre os profissionais do seu estabelecimento (barbeiros ou nail designers).</p>

    <?php if ($erro): ?>
      <div class="alert alert-error"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <?php if ($sucesso): ?>
      <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <div class="grid-two">
      <div class="card form-card">
        <h2>Novo profissional</h2>
        <form method="post" class="form-agendamento">
          <input type="hidden" name="cadastrar_profissional" value="1">
          <div class="form-group">
            <label for="nome">Nome *</label>
            <input type="text" id="nome" name="nome" required placeholder="Ex: João Barbeiro"
                   value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
          </div>
          <button type="submit" class="btn btn-primary">Cadastrar</button>
        </form>
      </div>
      <div class="card list-card">
        <h2>Profissionais</h2>
        <?php if (empty($profissionais)): ?>
          <p class="empty-msg">Nenhum profissional cadastrado. Cadastre para poder definir horários e receber agendamentos.</p>
        <?php else: ?>
          <ul class="agendamentos-list">
            <?php foreach ($profissionais as $p): ?>
              <li class="agendamento-item">
                <strong><?= htmlspecialchars($p['nome']) ?></strong>
                <span class="agendamento-pagamento"><?= htmlspecialchars($p['modalidade_nome']) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
