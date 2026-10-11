<?php

declare(strict_types=1);

namespace App\Enum;

enum AppEnvironmentEnum: string
{
  case Development = 'development';
  case Production = 'production';
  case Test = 'test';

  public static function isDevelopment(string $environment): bool
  {
    return $environment === self::Development->value;
  }

  public static function isProduction(string $environment): bool
  {
    return $environment === self::Production->value;
  }
}
