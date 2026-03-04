<?php

declare(strict_types=1);

/**
 * BarbaBook - Configuração da aplicação
 * Se o site estiver em um subdiretório (ex: /barbabook), defina SITE_BASE = '/barbabook'
 */
define('SITE_BASE', getenv('SITE_BASE') ?: '');
