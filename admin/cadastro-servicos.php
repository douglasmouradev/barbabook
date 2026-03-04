<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';

admin_require_login();

if (admin_multitenant_ativo() && admin_estabelecimento_efetivo_id() === null) {
    header('Location: ' . (SITE_BASE ?: '') . '/admin/cadastro-estabelecimentos.php');
    exit;
}

$pageTitle = 'Cadastro de Serviços';
$modality  = null;
$estabelecimento_id = admin_multitenant_ativo() ? admin_estabelecimento_efetivo_id() : null;
$tipo_estab = admin_multitenant_ativo() ? admin_estabelecimento_efetivo_tipo() : null;

$erro    = null;
$sucesso = null;

// Modalidades: no multitenant só a do estabelecimento
$modalidades = [];
if ($tipo_estab) {
    $modalidades = $pdo->prepare("SELECT id, slug, nome FROM modalidades WHERE ativo = 1 AND slug = ? ORDER BY slug");
    $modalidades->execute([$tipo_estab]);
    $modalidades = $modalidades->fetchAll(PDO::FETCH_ASSOC);
} else {
    $modalidades = $pdo->query("SELECT id, slug, nome FROM modalidades WHERE ativo = 1 ORDER BY slug")->fetchAll(PDO::FETCH_ASSOC);
}

// Listar serviços (do estabelecimento no multitenant)
$servicos = [];
try {
    $sql = "
        SELECT s.id, s.nome, s.duracao_minutos, s.preco, s.ativo, m.slug AS modalidade_slug, m.nome AS modalidade_nome
        FROM servicos s
        JOIN modalidades m ON m.id = s.modalidade_id
    ";
    $params = [];
    if ($estabelecimento_id !== null) {
        $sql .= " WHERE s.estabelecimento_id = ?";
        $params[] = $estabelecimento_id;
    }
    $sql .= " ORDER BY m.slug, s.nome";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = 'Erro ao carregar serviços.';
}

// Cadastrar novo serviço
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_servico'])) {
    $nome    = trim((string) ($_POST['nome'] ?? ''));
    $modalidade_id = (int) ($_POST['modalidade_id'] ?? 0);
    $duracao = (int) ($_POST['duracao_minutos'] ?? 30);
    $preco   = (float) str_replace(',', '.', (string) ($_POST['preco'] ?? '0'));

    if ($nome === '') {
        $erro = 'Informe o nome do serviço.';
    } elseif ($modalidade_id <= 0) {
        $erro = 'Selecione a modalidade.';
    } elseif ($duracao <= 0) {
        $erro = 'Duração deve ser maior que zero.';
    } elseif ($estabelecimento_id !== null && $tipo_estab) {
        $mod = $pdo->prepare("SELECT id FROM modalidades WHERE id = ? AND slug = ?");
        $mod->execute([$modalidade_id, $tipo_estab]);
        if (!$mod->fetch()) {
            $erro = 'Modalidade inválida para seu estabelecimento.';
        }
    }

    if ($erro === null) {
        try {
            if ($estabelecimento_id !== null) {
                $ins = $pdo->prepare("INSERT INTO servicos (modalidade_id, estabelecimento_id, nome, duracao_minutos, preco, ativo) VALUES (?, ?, ?, ?, ?, 1)");
                $ins->execute([$modalidade_id, $estabelecimento_id, $nome, $duracao, $preco]);
            } else {
                $ins = $pdo->prepare("INSERT INTO servicos (modalidade_id, nome, duracao_minutos, preco, ativo) VALUES (?, ?, ?, ?, 1)");
                $ins->execute([$modalidade_id, $nome, $duracao, $preco]);
            }
            $sucesso = 'Serviço cadastrado com sucesso.';
            $sql = "
                SELECT s.id, s.nome, s.duracao_minutos, s.preco, s.ativo, m.slug AS modalidade_slug, m.nome AS modalidade_nome
                FROM servicos s
                JOIN modalidades m ON m.id = s.modalidade_id
            ";
            $params = [];
            if ($estabelecimento_id !== null) {
                $sql .= " WHERE s.estabelecimento_id = ?";
                $params[] = $estabelecimento_id;
            }
            $sql .= " ORDER BY m.slug, s.nome";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $erro = 'Erro ao salvar serviço. Tente novamente.';
        }
    }
}

require __DIR__ . '/../includes/header.php';
$base = SITE_BASE ?: '';
$adminPage = 'servicos';
require __DIR__ . '/../includes/admin-sidebar.php';
?>

<section class="agendamentos-page">
  <div class="container">
    <h1 class="page-title">Cadastro de Serviços</h1>
    <p class="page-desc">Cadastre novos serviços por modalidade (Barbeiro ou Nails).</p>

    <?php if ($erro): ?>
      <div class="alert alert-error"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <?php if ($sucesso): ?>
      <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <div class="grid-two">
      <div class="card form-card">
        <h2>Novo serviço</h2>
        <form method="post" class="form-agendamento">
          <input type="hidden" name="cadastrar_servico" value="1">
          <div class="form-group">
            <label for="modalidade_id">Modalidade *</label>
            <select id="modalidade_id" name="modalidade_id" required>
              <option value="">Selecione</option>
              <?php foreach ($modalidades as $m): ?>
                <option value="<?= (int) $m['id'] ?>"><?= htmlspecialchars($m['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="nome">Nome do serviço *</label>
            <input type="text" id="nome" name="nome" required placeholder="Ex: Corte masculino">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="duracao_minutos">Duração (min) *</label>
              <input type="number" id="duracao_minutos" name="duracao_minutos" min="5" step="5" value="30">
            </div>
            <div class="form-group">
              <label for="preco">Preço (R$) *</label>
              <input type="text" id="preco" name="preco" placeholder="0,00" value="0,00">
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Cadastrar serviço</button>
        </form>
      </div>

      <div class="card list-card">
        <h2>Serviços cadastrados</h2>
        <?php if (empty($servicos)): ?>
          <p class="empty-msg">Nenhum serviço cadastrado.</p>
        <?php else: ?>
          <ul class="agendamentos-list">
            <?php foreach ($servicos as $s): ?>
              <li class="agendamento-item">
                <strong><?= htmlspecialchars($s['nome']) ?></strong>
                <span class="agendamento-pagamento"><?= htmlspecialchars($s['modalidade_nome']) ?></span>
                <?= (int) $s['duracao_minutos'] ?> min – R$ <?= number_format((float) $s['preco'], 2, ',', '.') ?>
                <?php if (!(int) $s['ativo']): ?>
                  <span class="status status-cancelado">inativo</span>
                <?php endif; ?>
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
