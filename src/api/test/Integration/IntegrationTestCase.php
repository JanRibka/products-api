<?php

declare(strict_types=1);

namespace Test\Integration;

use App\Entity\Brand\Implementation\Brand;
use App\Entity\Material\Implementation\Material;
use App\Entity\Product\Implementation\Product;
use App\Fixture\FixtureData;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

/**
 * Base class for HTTP-level integration tests.
 *
 * Every test boots a fresh container and recreates a deterministic fixture set
 * through the ORM, so assertions on order/pagination are stable and a request
 * that errors cannot leak state into the next test.
 */
abstract class IntegrationTestCase extends TestCase
{
  protected ContainerInterface $container;

  protected App $app;

  protected EntityManagerInterface $entityManager;

  /** @var array<string, int> */
  protected array $brandIds = [];

  /** @var array<string, int> */
  protected array $materialIds = [];

  protected function setUp(): void
  {
    $this->container = AppFactory::boot();
    AppFactory::recreateSchema($this->container);

    $this->app = AppFactory::app($this->container);
    $this->entityManager = $this->container->get(EntityManagerInterface::class);
    $this->seed();
  }

  /**
   * Deterministic seed used by the tests.
   *
   * It loads the exact same data as the application fixtures (the single
   * source of truth in App\Fixture\FixtureData), so assertions about counts,
   * ordering, filters and the export match the real seeded application. The
   * data has price ties (968 twice, 1331 three times) and repeats
   * brands/materials, which forces ties across page boundaries.
   */
  protected function seed(): void
  {
    $this->entityManager->clear();

    $data = FixtureData::get();

    $brands = [];
    foreach ($data['brands'] as $name) {
      $brand = new Brand();
      $brand->setName($name);
      $brands[$name] = $brand;
      $this->entityManager->persist($brand);
    }

    $materials = [];
    foreach ($data['materials'] as $name) {
      $material = new Material();
      $material->setName($name);
      $materials[$name] = $material;
      $this->entityManager->persist($material);
    }

    $this->entityManager->flush();

    $this->brandIds = [];
    foreach ($brands as $name => $brand) {
      $this->brandIds[$name] = $brand->getId();
    }
    $this->materialIds = [];
    foreach ($materials as $name => $material) {
      $this->materialIds[$name] = $material->getId();
    }

    foreach ($data['products'] as $row) {
      $product = new Product();
      $product
        ->setCode($row['code'])
        ->setName($row['name'])
        ->setDescription($row['description'])
        ->setPrice($row['price'])
        ->setBrand($brands[$row['brand']])
        ->setMaterial($materials[$row['material']])
        ->setCreatedAt()
        ->setUpdatedAt();
      $this->entityManager->persist($product);
    }

    $this->entityManager->flush();
    $this->entityManager->clear();
  }

  protected function request(string $method, string $uri, ?string $body = null, array $headers = []): ResponseInterface
  {
    $request = (new ServerRequestFactory())->createServerRequest($method, $uri);
    if ($body !== null) {
      $request = $request
        ->withBody((new StreamFactory())->createStream($body))
        ->withHeader('Content-Type', 'application/json');
    }
    foreach ($headers as $name => $value) {
      $request = $request->withHeader($name, $value);
    }

    return $this->app->handle($request);
  }

  protected function get(string $uri, array $headers = []): ResponseInterface
  {
    return $this->request('GET', $uri, null, $headers);
  }

  protected function put(string $uri, array|string $body, array $headers = []): ResponseInterface
  {
    $payload = is_array($body) ? json_encode($body, JSON_THROW_ON_ERROR) : $body;

    return $this->request('PUT', $uri, $payload, $headers);
  }

  protected function options(string $uri, array $headers = []): ResponseInterface
  {
    return $this->request('OPTIONS', $uri, null, $headers);
  }

  /**
   * @return array<string, mixed>
   */
  protected function json(ResponseInterface $response): array
  {
    $decoded = json_decode((string) $response->getBody(), true);

    if (!is_array($decoded)) {
      $this->fail('Response body is not valid JSON: ' . (string) $response->getBody());
    }

    return $decoded;
  }

  protected function assertJsonContentType(ResponseInterface $response): void
  {
    $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
  }

  /**
   * Boot an additional application instance with specific environment values.
   *
   * Used by the error-handling tests to exercise production vs development
   * configuration without touching the shared instance.
   *
   * @param array<string, string> $envOverrides
   * @return array{app: App, container: ContainerInterface}
   */
  protected function bootWithEnv(array $envOverrides): array
  {
    $container = AppFactory::boot($envOverrides);

    return ['app' => AppFactory::app($container), 'container' => $container];
  }
}
