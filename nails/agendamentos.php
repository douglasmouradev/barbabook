<?php

declare(strict_types=1);

/**
 * BarbaBook - Agendamentos de Unhas e afins (Nails / Nail Design)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/csrf.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Agendamentos Nails';
$modality  = 'nails';

$servicos         = [];
$profissionais    = [];
$erro             = null;
$sucesso          = null;
$resumoAgendamento = null;
$estabelecimento  = null;
$estabelecimento_id = null;

$multitenant = (bool) $pdo->query("SHOW TABLES LIKE 'estabelecimentos'")->fetch();
if ($multitenant) {
    $slug = trim((string) ($_GET['e'] ?? $_POST['e'] ?? ''));
    if ($slug === '') {
        header('Location: ' . (SITE_BASE ?: '') . '/nails/estabelecimentos.php');
        exit;
    }
    $stmtE = $pdo->prepare("SELECT id, nome, slug, tipo FROM estabelecimentos WHERE slug = ? AND tipo = 'nails' AND ativo = 1 LIMIT 1");
    $stmtE->execute([$slug]);
    $estabelecimento = $stmtE->fetch(PDO::FETCH_ASSOC);
    if (!$estabelecimento) {
        header('Location: ' . (SITE_BASE ?: '') . '/nails/estabelecimentos.php');
        exit;
    }
    $estabelecimento_id = (int) $estabelecimento['id'];
}

try {
    $midNails = (int) $pdo->query("SELECT id FROM modalidades WHERE slug = 'nails' LIMIT 1")->fetchColumn();
    $sqlServ = "SELECT id, nome, duracao_minutos, preco FROM servicos WHERE modalidade_id = ? AND ativo = 1";
    $paramsServ = [$midNails];
    if ($estabelecimento_id !== null) {
        $sqlServ .= " AND estabelecimento_id = ?";
        $paramsServ[] = $estabelecimento_id;
    }
    $sqlServ .= " ORDER BY nome";
    $stmtServ = $pdo->prepare($sqlServ);
    $stmtServ->execute($paramsServ);
    $servicos = $stmtServ->fetchAll(PDO::FETCH_ASSOC);

    $sqlProf = "SELECT id, nome FROM profissionais WHERE modalidade_id = ? AND ativo = 1";
    $paramsProf = [$midNails];
    if ($estabelecimento_id !== null) {
        $sqlProf .= " AND estabelecimento_id = ?";
        $paramsProf[] = $estabelecimento_id;
    }
    $sqlProf .= " ORDER BY nome";
    $stmtProf = $pdo->prepare($sqlProf);
    $stmtProf->execute($paramsProf);
    $profissionais = $stmtProf->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = 'Não foi possível carregar serviços ou profissionais.';
}

// Resumo após redirect (multitenant)
if ($estabelecimento && isset($_GET['sucesso']) && (int) $_GET['sucesso'] === 1 && !empty($_SESSION['agendamento_resumo'])) {
    $resumoAgendamento = $_SESSION['agendamento_resumo'];
    $sucesso = 'Agendamento criado com sucesso.';
    unset($_SESSION['agendamento_resumo']);
}

// Processar formulário de novo agendamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_agendamento']) && csrf_validate()) {
    $cliente_nome     = trim((string) ($_POST['cliente_nome'] ?? ''));
    $cliente_telefone = trim((string) ($_POST['cliente_telefone'] ?? ''));
    $cliente_email    = trim((string) ($_POST['cliente_email'] ?? ''));
    $servico_id       = (int) ($_POST['servico_id'] ?? 0);
    $profissional_id  = (int) ($_POST['profissional_id'] ?? 0);
    $data_agendamento = trim((string) ($_POST['data_agendamento'] ?? ''));
    $hora_inicio      = trim((string) ($_POST['hora_inicio'] ?? ''));
    $forma_pagamento  = trim((string) ($_POST['forma_pagamento'] ?? ''));
    $observacoes      = trim((string) ($_POST['observacoes'] ?? ''));

    $ok = true;
    if ($cliente_nome === '') { $erro = 'Informe o nome do cliente.'; $ok = false; }
    if ($ok && $cliente_telefone === '') { $erro = 'Informe o telefone.'; $ok = false; }
    if ($ok && $servico_id <= 0) { $erro = 'Selecione um serviço.'; $ok = false; }
    if ($ok && $profissional_id <= 0) { $erro = 'Selecione um profissional.'; $ok = false; }
    if ($ok && $data_agendamento === '') { $erro = 'Informe a data.'; $ok = false; }
    if ($ok && $hora_inicio === '') { $erro = 'Informe o horário.'; $ok = false; }
    if ($ok && $forma_pagamento === '') { $erro = 'Selecione a forma de pagamento.'; $ok = false; }
    $formas_permitidas = ['pix', 'dinheiro', 'credito', 'debito', 'transferencia'];
    if ($ok && !in_array($forma_pagamento, $formas_permitidas, true)) { $erro = 'Forma de pagamento inválida.'; $ok = false; }

    if ($ok) {
        try {
            $modalidade_id = (int) $pdo->query("SELECT id FROM modalidades WHERE slug = 'nails' LIMIT 1")->fetchColumn();
            $servico = $pdo->prepare("SELECT duracao_minutos FROM servicos WHERE id = ?");
            $servico->execute([$servico_id]);
            $duracao = (int) $servico->fetchColumn();
            $horaFim = date('H:i', strtotime($hora_inicio) + $duracao * 60);

            $telefone_digits = preg_replace('/\D/', '', $cliente_telefone);
            $tem_forma_pagamento = (bool) $pdo->query("
                SELECT 1 FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'agendamentos' AND COLUMN_NAME = 'forma_pagamento'
            ")->fetch();

            $colEstab = $estabelecimento_id !== null;
            if ($tem_forma_pagamento) {
                if ($colEstab) {
                    $ins = $pdo->prepare("
                        INSERT INTO agendamentos
                        (modalidade_id, estabelecimento_id, profissional_id, servico_id, cliente_nome, cliente_telefone, cliente_email, data_agendamento, hora_inicio, hora_fim, forma_pagamento, observacoes, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente')
                    ");
                    $ins->execute([
                        $modalidade_id, $estabelecimento_id, $profissional_id, $servico_id, $cliente_nome,
                        $telefone_digits ?: $cliente_telefone, $cliente_email ?: null,
                        $data_agendamento, $hora_inicio, $horaFim, $forma_pagamento, $observacoes ?: null,
                    ]);
                } else {
                    $ins = $pdo->prepare("
                        INSERT INTO agendamentos
                        (modalidade_id, profissional_id, servico_id, cliente_nome, cliente_telefone, cliente_email, data_agendamento, hora_inicio, hora_fim, forma_pagamento, observacoes, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente')
                    ");
                    $ins->execute([
                        $modalidade_id, $profissional_id, $servico_id, $cliente_nome,
                        $telefone_digits ?: $cliente_telefone, $cliente_email ?: null,
                        $data_agendamento, $hora_inicio, $horaFim, $forma_pagamento, $observacoes ?: null,
                    ]);
                }
            } else {
                if ($colEstab) {
                    $ins = $pdo->prepare("
                        INSERT INTO agendamentos
                        (modalidade_id, estabelecimento_id, profissional_id, servico_id, cliente_nome, cliente_telefone, cliente_email, data_agendamento, hora_inicio, hora_fim, observacoes, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente')
                    ");
                    $ins->execute([
                        $modalidade_id, $estabelecimento_id, $profissional_id, $servico_id, $cliente_nome,
                        $telefone_digits ?: $cliente_telefone, $cliente_email ?: null,
                        $data_agendamento, $hora_inicio, $horaFim, $observacoes ?: null,
                    ]);
                } else {
                    $ins = $pdo->prepare("
                        INSERT INTO agendamentos
                        (modalidade_id, profissional_id, servico_id, cliente_nome, cliente_telefone, cliente_email, data_agendamento, hora_inicio, hora_fim, observacoes, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente')
                    ");
                    $ins->execute([
                        $modalidade_id, $profissional_id, $servico_id, $cliente_nome,
                        $telefone_digits ?: $cliente_telefone, $cliente_email ?: null,
                        $data_agendamento, $hora_inicio, $horaFim, $observacoes ?: null,
                    ]);
                }
            }
            $servico_nome = '';
            foreach ($servicos as $s) {
                if ((int) $s['id'] === $servico_id) {
                    $servico_nome = $s['nome'];
                    break;
                }
            }
            $profissional_nome = '';
            foreach ($profissionais as $p) {
                if ((int) $p['id'] === $profissional_id) {
                    $profissional_nome = $p['nome'];
                    break;
                }
            }
            $rotulosPagamento = [
                'pix' => 'PIX', 'dinheiro' => 'Dinheiro', 'credito' => 'Cartão de crédito',
                'debito' => 'Cartão de débito', 'transferencia' => 'Transferência bancária',
            ];
            $resumoAgendamento = [
                'cliente_nome'      => $cliente_nome,
                'cliente_telefone'  => $cliente_telefone,
                'cliente_email'     => $cliente_email,
                'servico_nome'      => $servico_nome,
                'profissional_nome' => $profissional_nome,
                'data'              => $data_agendamento,
                'hora_inicio'       => $hora_inicio,
                'hora_fim'          => $horaFim,
                'forma_pagamento'   => $rotulosPagamento[$forma_pagamento] ?? $forma_pagamento,
                'observacoes'       => $observacoes,
            ];
            if ($estabelecimento) {
                $_SESSION['agendamento_resumo'] = $resumoAgendamento;
                header('Location: ' . (SITE_BASE ?: '') . '/nails/agendamentos.php?e=' . rawurlencode($estabelecimento['slug']) . '&sucesso=1');
                exit;
            }
            $_POST = [];
        } catch (PDOException $e) {
            $erro = 'Erro ao salvar agendamento. Tente novamente.';
        }
    }
}

// Meus agendamentos: só exibe se a pessoa informar o telefone (vê apenas os dela)
$agendamentos = [];
$consulta_telefone = trim((string) ($_GET['consulta_telefone'] ?? $_POST['consulta_telefone'] ?? ''));
$telefone_busca = $consulta_telefone !== '' ? preg_replace('/\D/', '', $consulta_telefone) : '';

if ($telefone_busca !== '') {
    try {
        $sqlMeus = "
            SELECT a.id, a.cliente_nome, a.cliente_telefone, a.data_agendamento, a.hora_inicio, a.hora_fim, a.forma_pagamento, a.status,
                   s.nome AS servico_nome, p.nome AS profissional_nome
            FROM agendamentos a
            JOIN servicos s ON s.id = a.servico_id
            JOIN profissionais p ON p.id = a.profissional_id
            WHERE a.modalidade_id = (SELECT id FROM modalidades WHERE slug = 'nails' LIMIT 1)
            AND a.data_agendamento >= CURDATE()
            AND REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(a.cliente_telefone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') = ?
        ";
        $paramsMeus = [$telefone_busca];
        if ($estabelecimento_id !== null) {
            $sqlMeus .= " AND a.estabelecimento_id = ?";
            $paramsMeus[] = $estabelecimento_id;
        }
        $sqlMeus .= " ORDER BY a.data_agendamento, a.hora_inicio LIMIT 50";
        $stmt = $pdo->prepare($sqlMeus);
        $stmt->execute($paramsMeus);
        $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // ignora
    }
}

require_once __DIR__ . '/../includes/header.php';
$base = SITE_BASE ?: '';
?>

<section class="agendamentos-page">
  <div class="container">
    <h1 class="page-title">Agendamentos – Nails<?= $estabelecimento ? ' – ' . htmlspecialchars($estabelecimento['nome']) : '' ?></h1>
    <p class="page-desc">Manicure, pedicure, alongamento e esmaltação em gel. Agende com nossas nail designers.</p>

    <?php if ($erro): ?>
      <div class="alert alert-error"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <?php if ($sucesso): ?>
      <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <?php if ($resumoAgendamento): ?>
      <div class="card card-resumo-agendamento">
        <h2>Resumo do agendamento</h2>
        <dl class="resumo-lista">
          <dt>Cliente</dt>
          <dd><?= htmlspecialchars($resumoAgendamento['cliente_nome']) ?></dd>
          <dt>Telefone</dt>
          <dd><?= htmlspecialchars($resumoAgendamento['cliente_telefone']) ?></dd>
          <?php if ($resumoAgendamento['cliente_email'] !== ''): ?>
            <dt>E-mail</dt>
            <dd><?= htmlspecialchars($resumoAgendamento['cliente_email']) ?></dd>
          <?php endif; ?>
          <dt>Serviço</dt>
          <dd><?= htmlspecialchars($resumoAgendamento['servico_nome']) ?></dd>
          <dt>Profissional</dt>
          <dd><?= htmlspecialchars($resumoAgendamento['profissional_nome']) ?></dd>
          <dt>Data</dt>
          <dd><?= date('d/m/Y', strtotime($resumoAgendamento['data'])) ?></dd>
          <dt>Horário</dt>
          <dd><?= date('H:i', strtotime($resumoAgendamento['hora_inicio'])) ?> – <?= date('H:i', strtotime($resumoAgendamento['hora_fim'])) ?></dd>
          <dt>Forma de pagamento</dt>
          <dd><?= htmlspecialchars($resumoAgendamento['forma_pagamento']) ?></dd>
          <?php if ($resumoAgendamento['observacoes'] !== ''): ?>
            <dt>Observações</dt>
            <dd><?= htmlspecialchars($resumoAgendamento['observacoes']) ?></dd>
          <?php endif; ?>
        </dl>
      </div>
    <?php endif; ?>

    <div class="grid-two">
      <div class="card form-card">
        <h2>Novo agendamento</h2>
        <form method="post" class="form-agendamento">
          <?= csrf_field() ?>
          <input type="hidden" name="criar_agendamento" value="1">
          <?php if ($estabelecimento): ?>
          <input type="hidden" name="e" value="<?= htmlspecialchars($estabelecimento['slug']) ?>">
          <?php endif; ?>
          <div class="form-group">
            <label for="cliente_nome">Nome do cliente *</label>
            <input type="text" id="cliente_nome" name="cliente_nome" required
                   value="<?= htmlspecialchars($_POST['cliente_nome'] ?? '') ?>">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="cliente_telefone">Telefone *</label>
              <input type="tel" id="cliente_telefone" name="cliente_telefone" required
                     value="<?= htmlspecialchars($_POST['cliente_telefone'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="cliente_email">E-mail</label>
              <input type="email" id="cliente_email" name="cliente_email"
                     value="<?= htmlspecialchars($_POST['cliente_email'] ?? '') ?>">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="servico_id">Serviço *</label>
              <select id="servico_id" name="servico_id" required>
                <option value="">Selecione</option>
                <?php foreach ($servicos as $s): ?>
                  <option value="<?= (int)$s['id'] ?>" <?= (int)($_POST['servico_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['nome']) ?> – R$ <?= number_format((float)$s['preco'], 2, ',', '.') ?> (<?= (int)$s['duracao_minutos'] ?> min)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="profissional_id">Profissional *</label>
              <select id="profissional_id" name="profissional_id" required>
                <option value="">Selecione</option>
                <?php foreach ($profissionais as $p): ?>
                  <option value="<?= (int)$p['id'] ?>" <?= (int)($_POST['profissional_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['nome']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="data_agendamento">Data *</label>
              <input type="date" id="data_agendamento" name="data_agendamento" required
                     value="<?= htmlspecialchars($_POST['data_agendamento'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="hora_inicio">Horário *</label>
              <input type="time" id="hora_inicio" name="hora_inicio" required
                     value="<?= htmlspecialchars($_POST['hora_inicio'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group">
            <label for="forma_pagamento">Forma de pagamento *</label>
            <select id="forma_pagamento" name="forma_pagamento" required>
              <option value="">Selecione</option>
              <?php
              $pagSel = $_POST['forma_pagamento'] ?? '';
              $opcoesPagamento = [
                  'pix'             => 'PIX',
                  'dinheiro'        => 'Dinheiro',
                  'credito'         => 'Cartão de crédito',
                  'debito'          => 'Cartão de débito',
                  'transferencia'   => 'Transferência bancária',
              ];
              foreach ($opcoesPagamento as $valor => $rotulo): ?>
                <option value="<?= $valor ?>" <?= $pagSel === $valor ? 'selected' : '' ?>><?= $rotulo ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="observacoes">Observações</label>
            <textarea id="observacoes" name="observacoes" rows="2"><?= htmlspecialchars($_POST['observacoes'] ?? '') ?></textarea>
          </div>
          <button type="submit" class="btn btn-primary">Criar agendamento</button>
        </form>
      </div>

      <div class="card list-card">
        <h2>Meus agendamentos – Nails</h2>
        <p class="empty-msg" style="margin-bottom:0.75rem;">Apenas seus agendamentos de unhas/nail design. Informe seu telefone para consultar.</p>
        <form method="get" class="form-agendamento" style="margin-bottom:1rem;">
          <?php if ($estabelecimento): ?>
          <input type="hidden" name="e" value="<?= htmlspecialchars($estabelecimento['slug']) ?>">
          <?php endif; ?>
          <div class="form-group" style="margin-bottom:0.5rem;">
            <label for="consulta_telefone">Telefone</label>
            <input type="tel" id="consulta_telefone" name="consulta_telefone" placeholder="(00) 00000-0000"
                   value="<?= htmlspecialchars($consulta_telefone) ?>">
          </div>
          <button type="submit" class="btn btn-primary">Ver meus agendamentos</button>
        </form>
        <?php if ($telefone_busca !== ''): ?>
          <?php if (empty($agendamentos)): ?>
            <p class="empty-msg">Nenhum agendamento futuro para este telefone.</p>
          <?php else: ?>
            <ul class="agendamentos-list">
              <?php foreach ($agendamentos as $a): ?>
                <li class="agendamento-item">
                  <span class="agendamento-data"><?= date('d/m/Y', strtotime($a['data_agendamento'])) ?> às <?= date('H:i', strtotime($a['hora_inicio'])) ?></span>
                  <strong><?= htmlspecialchars($a['servico_nome']) ?></strong> – <?= htmlspecialchars($a['profissional_nome']) ?>
                  <?php if (!empty($a['forma_pagamento'])): ?>
                    <span class="agendamento-pagamento">Pagamento: <?= htmlspecialchars($a['forma_pagamento']) ?></span>
                  <?php endif; ?>
                  <span class="status status-<?= htmlspecialchars($a['status']) ?>"><?= htmlspecialchars($a['status']) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="servicos-box card">
      <h2>Serviços disponíveis – Unhas e afins</h2>
      <ul class="servicos-list">
        <?php foreach ($servicos as $s): ?>
          <li><?= htmlspecialchars($s['nome']) ?> – <?= (int)$s['duracao_minutos'] ?> min – R$ <?= number_format((float)$s['preco'], 2, ',', '.') ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
