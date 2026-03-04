<?php

declare(strict_types=1);

function admin_is_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

function admin_require_login(): void
{
    if (!admin_is_logged_in()) {
        $base = defined('SITE_BASE') ? SITE_BASE : '';
        header('Location: ' . $base . '/admin/login.php');
        exit;
    }
}

function admin_current_name(): ?string
{
    return $_SESSION['admin_nome'] ?? null;
}

/** Multibeneficiário: ID do estabelecimento do admin logado (null = super-admin) */
function admin_estabelecimento_id(): ?int
{
    $id = $_SESSION['admin_estabelecimento_id'] ?? null;
    return $id === null ? null : (int) $id;
}

/** Tipo do estabelecimento: barbeiro ou nails */
function admin_estabelecimento_tipo(): ?string
{
    return isset($_SESSION['admin_estabelecimento_tipo']) ? (string) $_SESSION['admin_estabelecimento_tipo'] : null;
}

/** Super-admin: ID do estabelecimento escolhido para gerenciar (quando “escolher estabelecimento”) */
function admin_estabelecimento_gestao_id(): ?int
{
    $id = $_SESSION['admin_estabelecimento_gestao_id'] ?? null;
    return $id === null ? null : (int) $id;
}

/** ID efetivo: se super-admin escolheu um estabelecimento, usa esse; senão usa o vinculado ao usuário */
function admin_estabelecimento_efetivo_id(): ?int
{
    if (admin_estabelecimento_id() !== null) {
        return admin_estabelecimento_id();
    }
    return admin_estabelecimento_gestao_id();
}

/** Tipo efetivo para filtros (agenda, serviços, etc.) */
function admin_estabelecimento_efetivo_tipo(): ?string
{
    if (admin_estabelecimento_id() !== null) {
        return admin_estabelecimento_tipo();
    }
    $t = $_SESSION['admin_estabelecimento_gestao_tipo'] ?? null;
    return $t === null ? null : (string) $t;
}

/** Super-admin: pode cadastrar novos estabelecimentos */
function admin_is_super(): bool
{
    return admin_estabelecimento_id() === null && admin_multitenant_ativo();
}

/** Verifica se a tabela estabelecimentos existe (multitenant ativo) */
function admin_multitenant_ativo(): bool
{
    static $ativo = null;
    if ($ativo === null && isset($GLOBALS['pdo'])) {
        try {
            $ativo = (bool) $GLOBALS['pdo']->query("SHOW TABLES LIKE 'estabelecimentos'")->fetch();
        } catch (Throwable $e) {
            $ativo = false;
        }
    }
    return $ativo ?? false;
}
