<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';

admin_require_login();

// Multibeneficiário: super-admin sem estabelecimento nem “escolha” vai para cadastro
if (admin_multitenant_ativo() && admin_estabelecimento_efetivo_id() === null) {
    header('Location: ' . (SITE_BASE ?: '') . '/admin/cadastro-estabelecimentos.php');
    exit;
}

$modalidade = isset($_GET['modalidade']) && in_array($_GET['modalidade'], ['barbeiro', 'nails'], true)
    ? $_GET['modalidade']
    : null;
if ($modalidade === null) {
    $modalidade = admin_multitenant_ativo() ? (admin_estabelecimento_efetivo_tipo() ?: 'barbeiro') : 'barbeiro';
    header('Location: ' . (SITE_BASE ?: '') . '/admin/dashboard.php?modalidade=' . $modalidade);
    exit;
}
// Só pode ver agenda do tipo do estabelecimento (efetivo)
if (admin_multitenant_ativo() && admin_estabelecimento_efetivo_tipo() !== null && admin_estabelecimento_efetivo_tipo() !== $modalidade) {
    header('Location: ' . (SITE_BASE ?: '') . '/admin/dashboard.php?modalidade=' . admin_estabelecimento_efetivo_tipo());
    exit;
}

$pageTitle = $modalidade === 'barbeiro' ? 'Agenda Barbeiro' : 'Agenda Nails';
$estabelecimento_id = admin_multitenant_ativo() ? admin_estabelecimento_efetivo_id() : null;

$dataSelecionada = isset($_GET['data']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['data'])
    ? (string) $_GET['data']
    : date('Y-m-d');

// Agendamentos: da modalidade e do estabelecimento (multitenant)
$sqlAg = "
  SELECT a.*,
         m.nome AS modalidade_nome,
         s.nome AS servico_nome,
         p.nome AS profissional_nome
  FROM agendamentos a
  JOIN modalidades m ON m.id = a.modalidade_id
  JOIN servicos s ON s.id = a.servico_id
  JOIN profissionais p ON p.id = a.profissional_id
  WHERE a.data_agendamento = ? AND m.slug = ?
";
$paramsAg = [$dataSelecionada, $modalidade];
if ($estabelecimento_id !== null) {
    $sqlAg .= " AND a.estabelecimento_id = ?";
    $paramsAg[] = $estabelecimento_id;
}
$sqlAg .= " ORDER BY a.hora_inicio";
$stmtAg = $pdo->prepare($sqlAg);
$stmtAg->execute($paramsAg);
$agendamentos = $stmtAg->fetchAll(PDO::FETCH_ASSOC);

// Profissionais: mesma modalidade e mesmo estabelecimento
$sqlProf = "
  SELECT p.id, p.nome, m.nome AS modalidade_nome
  FROM profissionais p
  JOIN modalidades m ON m.id = p.modalidade_id
  WHERE p.ativo = 1 AND m.slug = ?
";
$paramsProf = [$modalidade];
if ($estabelecimento_id !== null) {
    $sqlProf .= " AND p.estabelecimento_id = ?";
    $paramsProf[] = $estabelecimento_id;
}
$sqlProf .= " ORDER BY p.nome";
$stmtProf = $pdo->prepare($sqlProf);
$stmtProf->execute($paramsProf);
$profissionais = $stmtProf->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/../includes/header.php';
$base = SITE_BASE ?: '';
$adminPage = 'dashboard';
require __DIR__ . '/../includes/admin-sidebar.php';
?>

<section class="agendamentos-page">
  <div class="container">
    <h1 class="page-title"><?= $modalidade === 'barbeiro' ? 'Agenda Barbeiro' : 'Agenda Nails' ?></h1>
    <p class="page-desc">Escolha a data para ver os agendamentos e a disponibilidade por profissional.</p>

    <form method="get" style="margin-bottom:1.5rem;">
      <input type="hidden" name="modalidade" value="<?= htmlspecialchars($modalidade) ?>">
      <label for="data" style="font-size:0.9rem;color:var(--text-muted);margin-right:0.5rem;">
        Escolha a data:
      </label>
      <input type="date" id="data" name="data"
             value="<?= htmlspecialchars($dataSelecionada) ?>">
      <button class="btn btn-primary" type="submit" style="margin-left:0.5rem;">Ver agenda</button>
    </form>

    <div class="card" style="margin-bottom:1.5rem;">
      <h2>Agendamentos – <?= date('d/m/Y', strtotime($dataSelecionada)) ?></h2>
      <?php if (empty($agendamentos)): ?>
        <p class="empty-msg">Nenhum agendamento para esta data.</p>
      <?php else: ?>
        <div style="overflow-x:auto;">
          <table style="width:100%;border-collapse:collapse;font-size:0.9rem;">
            <thead>
              <tr style="border-bottom:1px solid var(--border);">
                <th style="text-align:left;padding:0.5rem;">Hora</th>
                <th style="text-align:left;padding:0.5rem;">Profissional</th>
                <th style="text-align:left;padding:0.5rem;">Serviço</th>
                <th style="text-align:left;padding:0.5rem;">Cliente</th>
                <th style="text-align:left;padding:0.5rem;">Pagamento</th>
                <th style="text-align:left;padding:0.5rem;">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($agendamentos as $a): ?>
                <tr style="border-top:1px solid var(--border);">
                  <td style="padding:0.4rem;white-space:nowrap;">
                    <?= date('H:i', strtotime($a['hora_inicio'])) ?> – <?= date('H:i', strtotime($a['hora_fim'])) ?>
                  </td>
                  <td style="padding:0.4rem;"><?= htmlspecialchars($a['profissional_nome']) ?></td>
                  <td style="padding:0.4rem;"><?= htmlspecialchars($a['servico_nome']) ?></td>
                  <td style="padding:0.4rem;">
                    <?= htmlspecialchars($a['cliente_nome']) ?><br>
                    <span class="agendamento-pagamento"><?= htmlspecialchars($a['cliente_telefone']) ?></span>
                  </td>
                  <td style="padding:0.4rem;"><?= htmlspecialchars($a['forma_pagamento'] ?? '') ?></td>
                  <td style="padding:0.4rem;">
                    <span class="status status-<?= htmlspecialchars($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="card">
      <h2>Disponibilidade por profissional</h2>
      <?php if (empty($profissionais)): ?>
        <p class="empty-msg">Nenhum profissional cadastrado para esta modalidade.</p>
      <?php else: ?>
        <?php
        $ocupadosPorProf = [];
        $sqlOcup = "
          SELECT a.profissional_id, a.hora_inicio, a.hora_fim
          FROM agendamentos a
          JOIN modalidades m ON m.id = a.modalidade_id
          WHERE a.data_agendamento = ? AND m.slug = ?
            AND a.status IN ('pendente','confirmado')
        ";
        $paramsOcup = [$dataSelecionada, $modalidade];
        if ($estabelecimento_id !== null) {
            $sqlOcup .= " AND a.estabelecimento_id = ?";
            $paramsOcup[] = $estabelecimento_id;
        }
        $stmtOcup = $pdo->prepare($sqlOcup);
        $stmtOcup->execute($paramsOcup);
        foreach ($stmtOcup->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ocupadosPorProf[(int) $row['profissional_id']][] = $row;
        }

        foreach ($profissionais as $prof):
            $pid = (int) $prof['id'];

            $stmtH = $pdo->prepare("
            SELECT hora_inicio, hora_fim
            FROM horarios_disponiveis
            WHERE profissional_id = ? AND data = ?
            ORDER BY hora_inicio
          ");
            $stmtH->execute([$pid, $dataSelecionada]);
            $slots = $stmtH->fetchAll(PDO::FETCH_ASSOC);
        ?>
          <div style="margin-bottom:1rem;">
            <strong><?= htmlspecialchars($prof['nome']) ?> (<?= htmlspecialchars($prof['modalidade_nome']) ?>)</strong><br>
            <?php if (empty($slots)): ?>
              <span class="empty-msg">Nenhum horário cadastrado para esta data.</span>
            <?php else: ?>
              <?php foreach ($slots as $slot):
                $livre = true;
                if (!empty($ocupadosPorProf[$pid])) {
                    foreach ($ocupadosPorProf[$pid] as $o) {
                        if (!($slot['hora_fim'] <= $o['hora_inicio'] || $slot['hora_inicio'] >= $o['hora_fim'])) {
                            $livre = false;
                            break;
                        }
                    }
                }
              ?>
                <span style="display:inline-block;margin:0.15rem 0.3rem;
                             padding:0.2rem 0.5rem;border-radius:6px;
                             border:1px solid var(--border);
                             font-size:0.8rem;
                             background:<?= $livre ? 'rgba(74,155,111,0.15)' : 'rgba(199,92,92,0.15)' ?>;">
                  <?= substr((string) $slot['hora_inicio'], 0, 5) ?>–<?= substr((string) $slot['hora_fim'], 0, 5) ?>
                  <?= $livre ? '(livre)' : '(ocupado)' ?>
                </span>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
