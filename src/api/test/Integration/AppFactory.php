<?php

declare(strict_types=1);

namespace Test\Integration;

use App\Config;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Psr\Container\ContainerInterface;
use Slim\App;

/**
 * Boots the real application container for integration tests.
 *
 * The container is wired against a dedicated test database so the suite never
 * touches development/production data, and the schema is recreated from the
 * current entity metadata on demand.
 *
 * The DB defaults are container-first (db:3306), because the suite is meant
 * to run inside the app container where the MySQL service is reached by
 * service name and container port. When running from the HOST, set
 * TEST_DB_HOST=127.0.0.1 and TEST_DB_PORT=<host port mapped to MySQL>
 * (MYSQL_PORT, default 3308).
 */
final class AppFactory
{
  /**
   * @param array<string, string> $envOverrides
   */
  public static function boot(array $envOverrides = []): ContainerInterface
  {
    require_once __DIR__ . '/../../vendor/autoload.php';

    if (!defined('APP_PATH')) {
      require_once __DIR__ . '/../../configs/path_constants.php';
    }

    if (!function_exists('LowerUpperCaseNumberSpecialCharRegex')) {
      require_once __DIR__ . '/../../constants/regexes.php';
    }

    $testDbName = $_ENV['TEST_DB_NAME'] ?? 'products-api-test';

    $env = [
      'APP_NAME' => 'Products API',
      'APP_VERSION' => '0.1.0',
      'APP_ENV' => 'test',
      'APP_DEBUG' => getenv('TEST_APP_DEBUG') ?: 'false',
      'APP_URL' => 'http://localhost:3011',
      'ALLOWED_ORIGINS' => 'http://localhost:3000,http://example.test',
      'DB_DRIVER' => 'pdo_mysql',
      'DB_HOST' => getenv('TEST_DB_HOST') ?: 'db',
      'DB_PORT' => getenv('TEST_DB_PORT') ?: '3306',
      'DB_NAME' => $testDbName,
      'DB_USER' => getenv('TEST_DB_USER') ?: 'productsuser',
      'DB_PASS' => getenv('TEST_DB_PASS') ?: 'productspassword',
    ];

    foreach (array_merge($env, $envOverrides) as $key => $value) {
      $_ENV[$key] = $value;
    }

    return require __DIR__ . '/../../bootstrap.php';
  }

  public static function recreateSchema(ContainerInterface $container): void
  {
    /** @var EntityManagerInterface $entityManager */
    $entityManager = $container->get(EntityManagerInterface::class);
    $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

    $schemaTool = new SchemaTool($entityManager);
    $schemaTool->dropSchema($metadata);
    $schemaTool->createSchema($metadata);
  }

  public static function app(ContainerInterface $container): App
  {
    return $container->get(App::class);
  }

  public static function config(ContainerInterface $container): Config
  {
    return $container->get(Config::class);
  }
}
