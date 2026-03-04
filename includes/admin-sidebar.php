<?php
if (!isset($base)) $base = '';
if (!isset($adminPage)) $adminPage = '';
$modalidade = $modalidade ?? '';
$multitenant = function_exists('admin_multitenant_ativo') && admin_multitenant_ativo();
$sou_super = $multitenant && function_exists('admin_is_super') && admin_is_super();
$tipo = $multitenant && function_exists('admin_estabelecimento_efetivo_tipo') ? admin_estabelecimento_efetivo_tipo() : null;
?>
<div class="admin-layout">
  <aside class="admin-sidebar">
    <nav class="admin-nav">
      <?php if ($multitenant && $sou_super): ?>
        <a href="<?= $base ?>/admin/cadastro-estabelecimentos.php" class="admin-nav-item <?= $adminPage === 'estabelecimentos' ? 'active' : '' ?>">Cadastro de Estabelecimentos</a>
      <?php endif; ?>
      <?php if ($multitenant && $tipo): ?>
        <a href="<?= $base ?>/admin/dashboard.php?modalidade=<?= $tipo ?>" class="admin-nav-item <?= $adminPage === 'dashboard' ? 'active' : '' ?>">Agenda do dia</a>
        <a href="<?= $base ?>/admin/cadastro-profissionais.php" class="admin-nav-item <?= $adminPage === 'profissionais' ? 'active' : '' ?>">Cadastro de Profissionais</a>
        <a href="<?= $base ?>/admin/cadastro-servicos.php" class="admin-nav-item <?= $adminPage === 'servicos' ? 'active' : '' ?>">Cadastro de Serviços</a>
        <a href="<?= $base ?>/admin/cadastro-horarios.php" class="admin-nav-item <?= $adminPage === 'horarios' ? 'active' : '' ?>">Cadastro de Horários</a>
        <a href="<?= $base ?>/admin/cadastro-estabelecimentos.php" class="admin-nav-item <?= $adminPage === 'estabelecimentos' ? 'active' : '' ?>">Meu estabelecimento</a>
      <?php else: ?>
        <a href="<?= $base ?>/admin/dashboard.php?modalidade=barbeiro" class="admin-nav-item <?= ($adminPage === 'dashboard' && $modalidade === 'barbeiro') ? 'active' : '' ?>">Agenda Barbeiro</a>
        <a href="<?= $base ?>/admin/dashboard.php?modalidade=nails" class="admin-nav-item <?= ($adminPage === 'dashboard' && $modalidade === 'nails') ? 'active' : '' ?>">Agenda Nails</a>
        <a href="<?= $base ?>/admin/cadastro-servicos.php" class="admin-nav-item <?= $adminPage === 'servicos' ? 'active' : '' ?>">Cadastro de Serviços</a>
        <a href="<?= $base ?>/admin/cadastro-horarios.php" class="admin-nav-item <?= $adminPage === 'horarios' ? 'active' : '' ?>">Cadastro de Horários</a>
      <?php endif; ?>
    </nav>
    <p class="admin-sidebar-user"><?= htmlspecialchars(admin_current_name() ?? 'Admin') ?></p>
    <a href="<?= $base ?>/admin/logout.php" class="admin-nav-item admin-nav-sair">Sair</a>
  </aside>
  <div class="admin-main">
