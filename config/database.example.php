<?php

declare(strict_types=1);

/**
 * BarbaBook - Configuração do banco de dados (PHP 8.x + MySQL 8)
 * Copie para config/database.php e ajuste ou use variáveis de ambiente.
 */

return [
    'host'     => getenv('DB_HOST') ?: 'localhost',
    'port'     => (int) (getenv('DB_PORT') ?: 3306),
    'dbname'   => getenv('DB_NAME') ?: 'barbabook',
    'charset'  => 'utf8mb4',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: 'sua_senha_aqui',
];
