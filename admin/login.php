<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$pageTitle = 'Login administrativo';
$modality  = null;

if (admin_is_logged_in()) {
    $base = SITE_BASE ?: '';
    if (admin_multitenant_ativo()) {
        $eid = admin_estabelecimento_id();
        $tipo = admin_estabelecimento_tipo();
        if ($eid === null && $tipo === null) {
            header('Location: ' . $base . '/admin/cadastro-estabelecimentos.php');
        } else {
            header('Location: ' . $base . '/admin/dashboard.php?modalidade=' . ($tipo ?: 'barbeiro'));
        }
    } else {
        header('Location: ' . $base . '/admin/dashboard.php');
    }
    exit;
}

$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $erro = 'Requisição inválida. Recarregue a página e tente novamente.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $senha = (string) ($_POST['senha'] ?? '');

        if ($email === '' || $senha === '') {
            $erro = 'Informe e-mail e senha.';
        } else {
        $multitenant = (bool) $pdo->query("SHOW TABLES LIKE 'estabelecimentos'")->fetch();
        $totalAdmins = (int) $pdo->query('SELECT COUNT(*) FROM usuarios_admin')->fetchColumn();

        if ($totalAdmins === 0) {
            $sql = $multitenant
                ? 'INSERT INTO usuarios_admin (nome, email, senha_hash, ativo, estabelecimento_id) VALUES (?, ?, ?, 1, NULL)'
                : 'INSERT INTO usuarios_admin (nome, email, senha_hash, ativo) VALUES (?, ?, ?, 1)';
            $cols = ['Administrador', $email, password_hash($senha, PASSWORD_DEFAULT)];
            if ($multitenant) {
                $pdo->prepare($sql)->execute($cols);
            } else {
                $pdo->prepare('INSERT INTO usuarios_admin (nome, email, senha_hash, ativo) VALUES (?, ?, ?, 1)')->execute($cols);
            }
        }

        $sqlUser = $multitenant
            ? 'SELECT u.id, u.nome, u.email, u.senha_hash, u.estabelecimento_id, e.tipo AS estabelecimento_tipo
               FROM usuarios_admin u
               LEFT JOIN estabelecimentos e ON e.id = u.estabelecimento_id
               WHERE u.email = ? AND u.ativo = 1 LIMIT 1'
            : 'SELECT id, nome, email, senha_hash FROM usuarios_admin WHERE email = ? AND ativo = 1 LIMIT 1';
        $stmt = $pdo->prepare($sqlUser);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($senha, $user['senha_hash'])) {
            $erro = 'Login ou senha inválidos.';
        } else {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $user['id'];
            $_SESSION['admin_nome'] = $user['nome'];
            if ($multitenant) {
                $_SESSION['admin_estabelecimento_id'] = isset($user['estabelecimento_id']) && $user['estabelecimento_id'] !== null
                    ? (int) $user['estabelecimento_id'] : null;
                $_SESSION['admin_estabelecimento_tipo'] = $user['estabelecimento_tipo'] ?? null;
            }

            $base = SITE_BASE ?: '';
            if ($multitenant) {
                if (empty($_SESSION['admin_estabelecimento_id'])) {
                    header('Location: ' . $base . '/admin/cadastro-estabelecimentos.php');
                } else {
                    header('Location: ' . $base . '/admin/dashboard.php?modalidade=' . ($_SESSION['admin_estabelecimento_tipo'] ?: 'barbeiro'));
                }
            } else {
                header('Location: ' . $base . '/admin/dashboard.php');
            }
            exit;
        }
    }
}

require __DIR__ . '/../includes/header.php';
$base = SITE_BASE ?: '';
?>

<section class="agendamentos-page">
  <div class="container">
    <h1 class="page-title">Login administrativo</h1>
    <p class="page-desc">Acesse para gerenciar a agenda do BarbaBook.</p>

    <?php if ($erro): ?>
      <div class="alert alert-error"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <div class="card form-card" style="max-width:420px;margin:0 auto;">
      <form method="post" class="form-agendamento">
        <?= csrf_field() ?>
        <div class="form-group">
          <label for="email">E-mail *</label>
          <input type="email" id="email" name="email"
                 required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="senha">Senha *</label>
          <input type="password" id="senha" name="senha" required>
        </div>
        <button type="submit" class="btn btn-primary">Entrar</button>
      </form>
      <p class="empty-msg" style="margin-top:0.75rem;">
        Na primeira vez que você fizer login, o usuário administrador será criado automaticamente
        com o e-mail e a senha que informar.
      </p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
