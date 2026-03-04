<?php

declare(strict_types=1);

/**
 * BarbaBook - Bootstrap: carrega config e conexão PDO
 */

require_once __DIR__ . '/database.php';

$dbConfig = require __DIR__ . '/database.php';

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $dbConfig['host'],
    $dbConfig['port'],
    $dbConfig['dbname'],
    $dbConfig['charset']
);

$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
// PHP 8.5+ deprecou PDO::MYSQL_ATTR_INIT_COMMAND; usar Pdo\Mysql::ATTR_INIT_COMMAND quando existir
if (defined('Pdo\Mysql::ATTR_INIT_COMMAND')) {
    $pdoOptions[\Pdo\Mysql::ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
} else {
    $pdoOptions[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
}

try {
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $pdoOptions);
} catch (PDOException $e) {
    if (php_sapi_name() === 'cli') {
        throw $e;
    }
    http_response_code(500);
    echo '<h1>Erro de conexão</h1><p>Configure o banco em config/database.php ou variáveis de ambiente.</p>';
    exit;
}
