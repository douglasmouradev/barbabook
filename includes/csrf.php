<?php

declare(strict_types=1);

/**
 * Proteção CSRF: gera e valida token em formulários.
 */

function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_validate(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        return false;
    }
    $token = trim((string) ($_POST['_csrf'] ?? ''));
    return $token !== '' && hash_equals($_SESSION['_csrf_token'] ?? '', $token);
}
