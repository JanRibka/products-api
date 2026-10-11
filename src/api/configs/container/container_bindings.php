<?php

declare(strict_types=1);

use App\Config;
use App\Repository\Contract\BrandRepositoryInterface;
use App\Repository\Contract\MaterialRepositoryInterface;
use App\Repository\Contract\ProductRepositoryInterface;
use App\Repository\Implementation\BrandRepository;
use App\Repository\Implementation\MaterialRepository;
use App\Repository\Implementation\ProductRepository;
use App\RequestValidator\Request\Contract\RequestValidatorFactoryInterface;
use App\RequestValidator\Request\Implementation\RequestValidatorFactory;
use App\Service\Contract\EntityManagerServiceInterface;
use App\Service\Implementation\EntityManagerService;
use App\Shared\CsvExporter;
use App\Shared\ResponseFormatter\ResponseFormatter;
use App\Shared\RouteEntityBindingStrategy;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Interfaces\RouteParserInterface;

return [
    // --- Project config ---
  App::class => function (ContainerInterface $container) {
    AppFactory::setContainer($container);

    $middlewarePath = CONFIG_PATH . '/middleware.php';
    $addMiddleware = file_exists($middlewarePath) ? require $middlewarePath : function (App $app) {
    };
    $router = require CONFIG_PATH . '/routes/web.php';

    $app = AppFactory::create();

    $app->getRouteCollector()->setDefaultInvocationStrategy(
      new RouteEntityBindingStrategy(
        $container->get(EntityManagerService::class)
      )
    );

    $router($app);
    $addMiddleware($app);

    return $app;
  },
  Config::class => \DI\create(Config::class)->constructor(
    require CONFIG_PATH . '/app.php'
  ),

    // --- Database ---
  EntityManagerInterface::class => function (Config $config) {
    $ormConfig = ORMSetup::createAttributeMetadataConfiguration(
      $config->get('doctrine.entity_dir'),
      $config->get('doctrine.dev_mode'),
    );

    $ormConfig->enableNativeLazyObjects(true);

    return new EntityManager(
      DriverManager::getConnection($config->get('doctrine.connection'), $ormConfig),
      $ormConfig
    );
  },

    // --- Factories ---
  RequestValidatorFactoryInterface::class => fn(ContainerInterface $container) => $container->get(
    RequestValidatorFactory::class
  ),
  ResponseFactoryInterface::class => fn(App $app) => $app->getResponseFactory(),

    // --- Services ---
  EntityManagerServiceInterface::class => fn(ContainerInterface $container) => $container->get(
    EntityManagerService::class
  ),

    // --- Repositories ---
  ProductRepositoryInterface::class => fn(ContainerInterface $container) => $container->get(
    ProductRepository::class
  ),
  BrandRepositoryInterface::class => fn(ContainerInterface $container) => $container->get(
    BrandRepository::class
  ),
  MaterialRepositoryInterface::class => fn(ContainerInterface $container) => $container->get(
    MaterialRepository::class
  ),

    // --- Other ---
  ResponseFormatter::class => fn() => new ResponseFormatter(),
  CsvExporter::class => fn() => new CsvExporter(),
  RouteParserInterface::class => fn(App $app) => $app->getRouteCollector()->getRouteParser(),
];
