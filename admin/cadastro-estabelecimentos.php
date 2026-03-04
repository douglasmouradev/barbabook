<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

admin_require_login();

$pageTitle = 'Cadastro de Estabelecimentos';
$modality  = null;

if (!admin_multitenant_ativo()) {
    header('Location: ' . (SITE_BASE ?: '') . '/admin/dashboard.php?modalidade=barbeiro');
    exit;
}

$erro    = null;
$sucesso = null;
$estabelecimentos = [];

try {
    $estabelecimentos = $pdo->query("
        SELECT id, nome, tipo, slug, ativo, criado_em
        FROM estabelecimentos
        ORDER BY tipo, nome
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = 'Erro ao carregar estabelecimentos.';
}

$id_criado = isset($_GET['criado']) ? (int) $_GET['criado'] : 0;
$sou_super = admin_estabelecimento_id() === null;
$meu_estab = null;
if (!$sou_super) {
    $eid = admin_estabelecimento_id();
    foreach ($estabelecimentos as $e) {
        if ((int) $e['id'] === $eid) {
            $meu_estab = $e;
            break;
        }
    }
}

// Escolher estabelecimento para gerenciar (super-admin ou admin que acabou de criar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['escolher_estabelecimento']) && csrf_validate()) {
    $id = (int) ($_POST['estabelecimento_id'] ?? 0);
    $pode_escolher = $sou_super;
    if (!$pode_escolher && $meu_estab !== null && (int) $meu_estab['id'] === $id) {
        $pode_escolher = true;
    }
    if ($pode_escolher && $id > 0) {
        foreach ($estabelecimentos as $e) {
            if ((int) $e['id'] === $id) {
                $_SESSION['admin_estabelecimento_gestao_id'] = $id;
                $_SESSION['admin_estabelecimento_gestao_tipo'] = $e['tipo'];
                header('Location: ' . (SITE_BASE ?: '') . '/admin/dashboard.php?modalidade=' . $e['tipo']);
                exit;
            }
        }
    }
}

// Criar novo estabelecimento (só super-admin ou quem ainda não tem)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_estabelecimento']) && csrf_validate()) {
    if (!$sou_super && $meu_estab !== null) {
        $erro = 'Você já está vinculado a um estabelecimento. Apenas o administrador geral pode cadastrar novos.';
    } else {
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $tipo = isset($_POST['tipo']) && in_array($_POST['tipo'], ['barbeiro', 'nails'], true) ? $_POST['tipo'] : '';
        $slug = trim((string) ($_POST['slug'] ?? ''));
        $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));
        $vincular_a_mim = !empty($_POST['vincular_a_mim']);
        $email_novo = trim((string) ($_POST['email_novo'] ?? ''));
        $senha_novo = (string) ($_POST['senha_novo'] ?? '');

        if ($nome === '') {
            $erro = 'Informe o nome do estabelecimento.';
        } elseif ($tipo === '') {
            $erro = 'Selecione o tipo (Barbearia ou Nails).';
        } elseif ($slug === '') {
            $erro = 'Informe um identificador (slug) para o estabelecimento.';
        } elseif (!$vincular_a_mim && ($email_novo === '' || $senha_novo === '')) {
            $erro = 'Para criar com novo administrador, informe e-mail e senha.';
        } else {
            try {
                $pdo->prepare("INSERT INTO estabelecimentos (nome, tipo, slug, ativo) VALUES (?, ?, ?, 1)")
                    ->execute([$nome, $tipo, $slug]);
                $novoId = (int) $pdo->lastInsertId();

                if ($vincular_a_mim) {
                    $pdo->prepare("UPDATE usuarios_admin SET estabelecimento_id = ? WHERE id = ?")
                        ->execute([$novoId, $_SESSION['admin_id']]);
                    $_SESSION['admin_estabelecimento_id'] = $novoId;
                    $_SESSION['admin_estabelecimento_tipo'] = $tipo;
                    $sucesso = 'Estabelecimento criado e vinculado a você. Selecione-o na caixa abaixo e clique em "Acessar agenda" para continuar.';
                    header('Location: ' . (SITE_BASE ?: '') . '/admin/cadastro-estabelecimentos.php?criado=' . $novoId);
                    exit;
                } else {
                    $pdo->prepare("
                        INSERT INTO usuarios_admin (nome, email, senha_hash, ativo, estabelecimento_id)
                        VALUES (?, ?, ?, 1, ?)
                    ")->execute([
                        $nome,
                        $email_novo,
                        password_hash($senha_novo, PASSWORD_DEFAULT),
                        $novoId,
                    ]);
                    $sucesso = 'Estabelecimento e administrador criados. Selecione o estabelecimento na caixa abaixo para gerenciá-lo.';
                    $estabelecimentos = $pdo->query("SELECT id, nome, tipo, slug, ativo, criado_em FROM estabelecimentos ORDER BY tipo, nome")->fetchAll(PDO::FETCH_ASSOC);
                    header('Location: ' . (SITE_BASE ?: '') . '/admin/cadastro-estabelecimentos.php?criado=' . $novoId);
                    exit;
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate') !== false) {
                    $erro = 'Já existe um estabelecimento com este identificador (slug) ou e-mail. Escolha outro.';
                } else {
                    $erro = 'Erro ao salvar. Tente novamente.';
                }
            }
        }
    }
}

require __DIR__ . '/../includes/header.php';
$base = SITE_BASE ?: '';
$adminPage = 'estabelecimentos';
require __DIR__ . '/../includes/admin-sidebar.php';
?>

<section class="agendamentos-page">
  <div class="container">
    <h1 class="page-title">Cadastro de Estabelecimentos</h1>
    <p class="page-desc">Cadastre uma barbearia ou um espaço de nails. Cada estabelecimento tem sua própria agenda e seus próprios dados.</p>

    <?php if ($erro): ?>
      <div class="alert alert-error"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <?php if ($sucesso): ?>
      <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <?php
    $mostrar_caixa_estabelecimento = !empty($estabelecimentos) && ($sou_super || $meu_estab !== null);
    $lista_para_selecao = $sou_super ? $estabelecimentos : ($meu_estab !== null ? [$meu_estab] : []);
    ?>
    <?php if ($mostrar_caixa_estabelecimento): ?>
      <div class="card" style="margin-bottom:1.5rem;">
        <h2>Escolher estabelecimento para gerenciar</h2>
        <?php if ($id_criado > 0): ?>
          <p class="alert alert-success" style="margin-bottom:1rem;">Estabelecimento criado. Selecione-o na caixa abaixo e clique em "Acessar agenda" para continuar.</p>
        <?php else: ?>
          <p class="page-desc">Selecione qual barbearia ou nails você quer acessar (agenda, serviços, horários).</p>
        <?php endif; ?>
        <form method="post" class="form-agendamento" style="max-width:400px;margin-top:1rem;">
          <?= csrf_field() ?>
          <input type="hidden" name="escolher_estabelecimento" value="1">
          <div class="form-group">
            <label for="estabelecimento_id">Estabelecimento *</label>
            <select id="estabelecimento_id" name="estabelecimento_id" required>
              <option value="">Selecione...</option>
              <?php foreach ($lista_para_selecao as $e): ?>
                <option value="<?= (int) $e['id'] ?>" <?= $id_criado > 0 && (int) $e['id'] === $id_criado ? 'selected' : '' ?>><?= htmlspecialchars($e['nome']) ?> (<?= $e['tipo'] === 'barbeiro' ? 'Barbearia' : 'Nails' ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Acessar agenda deste estabelecimento</button>
        </form>
      </div>
    <?php endif; ?>

    <?php if ($meu_estab): ?>
      <div class="card" style="margin-bottom:1.5rem;">
        <h2>Seu estabelecimento</h2>
        <p><strong><?= htmlspecialchars($meu_estab['nome']) ?></strong> – <?= $meu_estab['tipo'] === 'barbeiro' ? 'Barbearia' : 'Nails' ?></p>
        <p class="empty-msg">Link para clientes agendarem: <strong><?= $base ?>/<?= $meu_estab['tipo'] ?>/agendamentos.php?e=<?= htmlspecialchars($meu_estab['slug']) ?></strong></p>
      </div>
    <?php endif; ?>

    <?php if ($sou_super || $meu_estab === null): ?>
      <div class="grid-two">
        <div class="card form-card">
          <h2><?= $meu_estab === null ? 'Criar seu estabelecimento' : 'Novo estabelecimento' ?></h2>
          <p class="empty-msg" style="margin-bottom:1rem;">Escolha Barbearia ou Nails e defina um identificador único (ex: minha-barbearia).</p>
          <form method="post" class="form-agendamento">
            <?= csrf_field() ?>
            <input type="hidden" name="criar_estabelecimento" value="1">
            <div class="form-group">
              <label for="nome">Nome do estabelecimento *</label>
              <input type="text" id="nome" name="nome" required placeholder="Ex: Barbearia do João"
                     value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="tipo">Tipo *</label>
              <select id="tipo" name="tipo" required>
                <option value="">Selecione</option>
                <option value="barbeiro" <?= ($_POST['tipo'] ?? '') === 'barbeiro' ? 'selected' : '' ?>>Barbearia</option>
                <option value="nails" <?= ($_POST['tipo'] ?? '') === 'nails' ? 'selected' : '' ?>>Nails</option>
              </select>
            </div>
            <div class="form-group">
              <label for="slug">Identificador único (slug) *</label>
              <input type="text" id="slug" name="slug" required placeholder="minha-barbearia"
                     value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>">
              <small style="color:var(--text-muted);">Apenas letras minúsculas, números e hífen. Será usado no link de agendamento.</small>
            </div>
            <div class="form-group">
              <label><input type="checkbox" name="vincular_a_mim" value="1" id="vincular_a_mim" <?= ($meu_estab === null ? 'checked' : '') ?>> Vincular meu usuário a este estabelecimento</label>
            </div>
            <p class="empty-msg" style="margin:0.5rem 0;">Ou crie outro administrador para este estabelecimento (deixe em branco se vinculou a você):</p>
            <div class="form-group">
              <label for="email_novo">E-mail do novo admin</label>
              <input type="email" id="email_novo" name="email_novo" value="<?= htmlspecialchars($_POST['email_novo'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="senha_novo">Senha do novo admin</label>
              <input type="password" id="senha_novo" name="senha_novo">
            </div>
            <button type="submit" class="btn btn-primary">Criar estabelecimento</button>
          </form>
        </div>

        <div class="card list-card">
          <h2>Estabelecimentos cadastrados</h2>
          <?php if (empty($estabelecimentos)): ?>
            <p class="empty-msg">Nenhum estabelecimento ainda.</p>
          <?php else: ?>
            <ul class="agendamentos-list">
              <?php foreach ($estabelecimentos as $e): ?>
                <li class="agendamento-item">
                  <strong><?= htmlspecialchars($e['nome']) ?></strong>
                  <span class="agendamento-pagamento"><?= $e['tipo'] === 'barbeiro' ? 'Barbearia' : 'Nails' ?></span>
                  <span>slug: <?= htmlspecialchars($e['slug']) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
