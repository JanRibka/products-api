<?php

declare(strict_types=1);

namespace Test\Integration\Http;

use RuntimeException;
use Test\Integration\IntegrationTestCase;

/**
 * B3: all errors must be JSON, never Slim's HTML error page with traces.
 */
class ErrorHandlingTest extends IntegrationTestCase
{
  private function appWithThrowingRoute(string $debug): \Slim\App
  {
    ['app' => $app, 'container' => $container] = $this->bootWithEnv(['APP_DEBUG' => $debug]);

    // Test-only route that fails at runtime, to exercise the 500 path.
    $app->get('/__test/throw', function () {
      throw new RuntimeException(
        'Secret internal failure at /var/www/html/secret.php with SQL SELECT * FROM users'
      );
    });

    return $app;
  }

  private function handle(
    \Slim\App $app,
    string $method,
    string $uri,
    ?string $body = null
  ): \Psr\Http\Message\ResponseInterface {
    $request = (new \Slim\Psr7\Factory\ServerRequestFactory())->createServerRequest($method, $uri);
    if ($body !== null) {
      $request = $request
        ->withBody((new \Slim\Psr7\Factory\StreamFactory())->createStream($body))
        ->withHeader('Content-Type', 'application/json');
    }

    return $app->handle($request);
  }

  public function testUnknownRouteReturnsJson404(): void
  {
    $response = $this->get('/api/does-not-exist');

    $this->assertSame(404, $response->getStatusCode());
    $this->assertJsonContentType($response);

    $body = $this->json($response);
    $this->assertArrayHasKey('error', $body);
  }

  public function testWrongMethodReturnsJson405WithAllowHeader(): void
  {
    $response = $this->request('POST', '/api/products');

    $this->assertSame(405, $response->getStatusCode());
    $this->assertJsonContentType($response);
    $this->assertNotSame('', $response->getHeaderLine('Allow'), 'Allow header should be present');

    $body = $this->json($response);
    $this->assertArrayHasKey('error', $body);
  }

  public function testForcedExceptionReturnsJson500WithoutTraceInProduction(): void
  {
    $app = $this->appWithThrowingRoute('false');
    $response = $this->handle($app, 'GET', '/__test/throw');

    $this->assertSame(500, $response->getStatusCode());
    $this->assertJsonContentType($response);

    $raw = (string) $response->getBody();
    $this->assertStringNotContainsString('RuntimeException', $raw);
    $this->assertStringNotContainsString('/var/www/html', $raw);
    $this->assertStringNotContainsString('SELECT * FROM users', $raw);
    $this->assertStringNotContainsString('#0 ', $raw);
    $this->assertStringNotContainsString('Stack trace', $raw);

    $body = $this->json($response);
    $this->assertArrayHasKey('error', $body);
  }

  public function testForcedExceptionInDevelopmentIsStillJson(): void
  {
    $app = $this->appWithThrowingRoute('true');
    $response = $this->handle($app, 'GET', '/__test/throw');

    $this->assertSame(500, $response->getStatusCode());
    $this->assertJsonContentType($response);
    $body = $this->json($response);
    $this->assertTrue(
      isset($body['error']) || isset($body['validationError']),
      'Expected a JSON error payload, got: ' . (string) $response->getBody()
    );
  }

  public function testMalformedJsonBodyOnPutReturnsCleanJson4xx(): void
  {
    $response = $this->put('/api/products/1', '{ this is not valid json ');

    $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
    $this->assertLessThan(500, $response->getStatusCode());
    $this->assertJsonContentType($response);
    $body = $this->json($response);
    $this->assertTrue(
      isset($body['error']) || isset($body['validationError']),
      'Expected a JSON error payload, got: ' . (string) $response->getBody()
    );
  }

  public function testEmptyBodyOnPutReturnsCleanJson4xx(): void
  {
    $response = $this->put('/api/products/1', '');

    $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
    $this->assertLessThan(500, $response->getStatusCode());
    $this->assertJsonContentType($response);
    $body = $this->json($response);
    $this->assertTrue(
      isset($body['error']) || isset($body['validationError']),
      'Expected a JSON error payload, got: ' . (string) $response->getBody()
    );
  }
}
