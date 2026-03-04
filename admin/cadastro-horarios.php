<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';

admin_require_login();

if (admin_multitenant_ativo() && admin_estabelecimento_efetivo_id() === null) {
    header('Location: ' . (SITE_BASE ?: '') . '/admin/cadastro-estabelecimentos.php');
    exit;
}

$pageTitle = 'Cadastro de Horários';
$modality  = null;
$estabelecimento_id = admin_multitenant_ativo() ? admin_estabelecimento_efetivo_id() : null;

$erro    = null;
$sucesso = null;

$sqlProf = "
    SELECT p.id, p.nome, m.slug AS modalidade_slug, m.nome AS modalidade_nome
    FROM profissionais p
    JOIN modalidades m ON m.id = p.modalidade_id
    WHERE p.ativo = 1
";
$paramsProf = [];
if ($estabelecimento_id !== null) {
    $sqlProf .= " AND p.estabelecimento_id = ?";
    $paramsProf[] = $estabelecimento_id;
}
$sqlProf .= " ORDER BY m.slug, p.nome";
$stmtProf = $pdo->prepare($sqlProf);
$stmtProf->execute($paramsProf);
$profissionais = $stmtProf->fetchAll(PDO::FETCH_ASSOC);

// Cadastrar novo horário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_horario'])) {
    $profissional_id = (int) ($_POST['profissional_id'] ?? 0);
    $data            = trim((string) ($_POST['data'] ?? ''));
    $hora_inicio     = trim((string) ($_POST['hora_inicio'] ?? ''));
    $hora_fim        = trim((string) ($_POST['hora_fim'] ?? ''));

    if ($profissional_id <= 0) {
        $erro = 'Selecione o profissional.';
    } elseif ($data === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        $erro = 'Informe a data.';
    } elseif ($hora_inicio === '' || $hora_fim === '') {
        $erro = 'Informe horário de início e fim.';
    } elseif (strtotime($hora_fim) <= strtotime($hora_inicio)) {
        $erro = 'Horário de fim deve ser depois do início.';
    } else {
        try {
            $ins = $pdo->prepare("INSERT INTO horarios_disponiveis (profissional_id, data, hora_inicio, hora_fim) VALUES (?, ?, ?, ?)");
            $ins->execute([$profissional_id, $data, $hora_inicio, $hora_fim]);
            $sucesso = 'Horário cadastrado com sucesso.';
        } catch (PDOException $e) {
            $erro = 'Erro ao salvar horário. Tente novamente.';
        }
    }
}

// Listar horários (próximos 30 dias) — só do estabelecimento no multitenant
$dataInicio = date('Y-m-d');
$dataFim    = date('Y-m-d', strtotime('+30 days'));
$horarios   = [];
try {
    $sqlHor = "
        SELECT h.id, h.data, h.hora_inicio, h.hora_fim, p.nome AS profissional_nome, m.nome AS modalidade_nome
        FROM horarios_disponiveis h
        JOIN profissionais p ON p.id = h.profissional_id
        JOIN modalidades m ON m.id = p.modalidade_id
        WHERE h.data BETWEEN ? AND ?
    ";
    $paramsHor = [$dataInicio, $dataFim];
    if ($estabelecimento_id !== null) {
        $sqlHor .= " AND p.estabelecimento_id = ?";
        $paramsHor[] = $estabelecimento_id;
    }
    $sqlHor .= " ORDER BY h.data, h.hora_inicio LIMIT 100";
    $stmt = $pdo->prepare($sqlHor);
    $stmt->execute($paramsHor);
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // ignora
}

require __DIR__ . '/../includes/header.php';
$base = SITE_BASE ?: '';
$adminPage = 'horarios';
require __DIR__ . '/../includes/admin-sidebar.php';
?>

<section class="agendamentos-page">
  <div class="container">
    <h1 class="page-title">Cadastro de Horários</h1>
    <p class="page-desc">Cadastre os horários em que cada profissional está disponível.</p>

    <?php if ($erro): ?>
      <div class="alert alert-error"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <?php if ($sucesso): ?>
      <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <div class="grid-two">
      <div class="card form-card">
        <h2>Novo horário disponível</h2>
        <p class="empty-msg" style="margin-bottom:1rem;">Cadastre um intervalo de horário em que o profissional está disponível na data escolhida.</p>
        <form method="post" class="form-agendamento">
          <input type="hidden" name="cadastrar_horario" value="1">
          <div class="form-group">
            <label for="profissional_id">Profissional *</label>
            <select id="profissional_id" name="profissional_id" required>
              <option value="">Selecione</option>
              <?php foreach ($profissionais as $p): ?>
                <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?> (<?= htmlspecialchars($p['modalidade_nome']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="data">Data *</label>
            <input type="date" id="data" name="data" required min="<?= $dataInicio ?>">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="hora_inicio">Das *</label>
              <input type="time" id="hora_inicio" name="hora_inicio" required>
            </div>
            <div class="form-group">
              <label for="hora_fim">Até *</label>
              <input type="time" id="hora_fim" name="hora_fim" required>
            </div>
          </div>
          <button type="submit" class="btn btn-primary">Cadastrar horário</button>
        </form>
      </div>

      <div class="card list-card">
        <h2>Horários cadastrados (próximos 30 dias)</h2>
        <?php if (empty($horarios)): ?>
          <p class="empty-msg">Nenhum horário cadastrado. Use o formulário ao lado para cadastrar.</p>
        <?php else: ?>
          <ul class="agendamentos-list">
            <?php foreach ($horarios as $h): ?>
              <li class="agendamento-item">
                <span class="agendamento-data"><?= date('d/m/Y', strtotime($h['data'])) ?></span>
                <strong><?= htmlspecialchars($h['profissional_nome']) ?></strong>
                <span class="agendamento-pagamento"><?= htmlspecialchars($h['modalidade_nome']) ?></span>
                <?= date('H:i', strtotime($h['hora_inicio'])) ?> – <?= date('H:i', strtotime($h['hora_fim'])) ?>
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
