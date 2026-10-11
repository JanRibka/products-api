<?php

declare(strict_types=1);

use Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

if (!defined('APP_PATH')) {
  require __DIR__ . '/configs/path_constants.php';
}

if (!function_exists('LowerUpperCaseNumberSpecialCharRegex')) {
  require __DIR__ . '/constants/regexes.php';
}

require __DIR__ . '/configs/routes/parts/parts.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

return require CONFIG_PATH . '/container/container.php';

