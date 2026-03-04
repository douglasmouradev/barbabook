<?php

declare(strict_types=1);

/**
 * Cria um usuário administrador padrão (execute uma vez).
 * Uso: php criar-admin.php
 *
 * Segurança: só pode ser executado pela linha de comando (CLI).
 * Não acesse pela web para evitar criação/reset de admin por terceiros.
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo 'Acesso negado. Execute via terminal: php criar-admin.php';
    exit(1);
}

/**
 * Credenciais: admin@barbabook.com / admin (troque após o primeiro login).
 */

require_once __DIR__ . '/config/bootstrap.php';

$email = 'admin@barbabook.com';
$senha = 'admin';
$nome  = 'Administrador';

$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios_admin WHERE email = ?");
    $stmt->execute([$email]);
    $existe = (int) $stmt->fetchColumn();
    if ($existe > 0) {
        $pdo->prepare("UPDATE usuarios_admin SET senha_hash = ?, nome = ? WHERE email = ?")
            ->execute([$senha_hash, $nome, $email]);
        echo "Usuário '$email' já existia. Senha foi atualizada.\n";
    } else {
        $temEstab = (bool) $pdo->query("SHOW TABLES LIKE 'estabelecimentos'")->fetch();
        if ($temEstab) {
            $pdo->prepare("INSERT INTO usuarios_admin (nome, email, senha_hash, ativo, estabelecimento_id) VALUES (?, ?, ?, 1, NULL)")
                ->execute([$nome, $email, $senha_hash]);
        } else {
            $pdo->prepare("INSERT INTO usuarios_admin (nome, email, senha_hash, ativo) VALUES (?, ?, ?, 1)")
                ->execute([$nome, $email, $senha_hash]);
        }
        echo "Administrador criado com sucesso.\n";
    }
    echo "\nCredenciais de acesso:\n  E-mail: $email\n  Senha:  $senha\n";
    echo "\nAcesse o login administrativo e use esses dados. Troque a senha depois.\n";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    exit(1);
}
