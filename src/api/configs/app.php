<?php

declare(strict_types=1);

use App\Enum\AppEnvironmentEnum;

$boolean = function (mixed $value) {
  if (in_array($value, ['true', 1, '1', true, 'yes', 'on'], true)) {
    return true;
  }

  return false;
};
$appEnv = $_ENV['APP_ENV'] ?? AppEnvironmentEnum::Production->value;

return [
  'app_name' => $_ENV['APP_NAME'],
  'app_version' => $_ENV['APP_VERSION'] ?? '1.0',
  'app_url' => $_ENV['APP_URL'] ?? 'http://localhost:3011',
  'app_environment' => $appEnv,
  'display_error_details' => $boolean($_ENV['APP_DEBUG'] ?? 0),
  'log_errors' => true,
  'log_error_details' => true,
  'allowed_origins' => array_values(array_filter(array_map(
    'trim',
    explode(',', $_ENV['ALLOWED_ORIGINS'] ?? '')
  ), static fn(string $origin): bool => $origin !== '')),
  'doctrine' => [
    'dev_mode' => AppEnvironmentEnum::isDevelopment($appEnv),
    'cache_dir' => STORAGE_PATH . '/cache/doctrine',
    'entity_dir' => [APP_PATH . '/Entity'],
    'connection' => [
      'driver' => $_ENV['DB_DRIVER'] ?? 'pdo_mysql',
      'host' => $_ENV['DB_HOST'] ?? 'localhost',
      'port' => isset($_ENV['DB_PORT']) && $_ENV['DB_PORT'] !== '' ? (int) $_ENV['DB_PORT'] : 3306,
      'dbname' => $_ENV['DB_NAME'],
      'user' => $_ENV['DB_USER'],
      'password' => $_ENV['DB_PASS'],
    ],
  ],
];
