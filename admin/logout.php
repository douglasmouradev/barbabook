<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$_SESSION = [];
session_destroy();

$base = SITE_BASE ?: '';
header('Location: ' . $base . '/');
exit;
