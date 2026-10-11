<?php

declare(strict_types=1);

namespace Test\Integration\Http;

use Test\Integration\IntegrationTestCase;

/**
 * CORS must be handled by the application (not raw header() calls) so the
 * allowlist works in every environment, including production.
 */
class CorsTest extends IntegrationTestCase
{
  public function testPreflightFromAllowedOriginReturns204WithCorsHeaders(): void
  {
    $response = $this->options('/api/products', [
      'Origin' => 'http://localhost:3000',
      'Access-Control-Request-Method' => 'GET',
    ]);

    $this->assertSame(204, $response->getStatusCode());
    $this->assertSame('http://localhost:3000', $response->getHeaderLine('Access-Control-Allow-Origin'));
    $this->assertStringContainsString('GET', $response->getHeaderLine('Access-Control-Allow-Methods'));
  }

  public function testPreflightFromDisallowedOriginHasNoCorsHeadersAtAll(): void
  {
    $response = $this->options('/api/products', [
      'Origin' => 'http://evil.example',
      'Access-Control-Request-Method' => 'GET',
    ]);

    $this->assertNoCorsHeaders($response);
  }

  public function testActualRequestFromAllowedOriginEchoesAllowOrigin(): void
  {
    $response = $this->get('/api/products', ['Origin' => 'http://localhost:3000']);

    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('http://localhost:3000', $response->getHeaderLine('Access-Control-Allow-Origin'));
  }

  public function testActualRequestFromDisallowedOriginHasNoCorsHeadersAtAll(): void
  {
    $response = $this->get('/api/products', ['Origin' => 'http://evil.example']);

    $this->assertSame(200, $response->getStatusCode());
    $this->assertNoCorsHeaders($response);
  }

  private function assertNoCorsHeaders(\Psr\Http\Message\ResponseInterface $response): void
  {
    $headers = [
      'Access-Control-Allow-Origin',
      'Access-Control-Allow-Methods',
      'Access-Control-Allow-Headers',
      'Access-Control-Allow-Credentials',
      'Access-Control-Max-Age',
    ];

    foreach ($headers as $header) {
      $this->assertFalse(
        $response->hasHeader($header),
        "Disallowed origin must not receive $header"
      );
    }
  }

  public function testCorsWorksInProductionEnvironment(): void
  {
    ['app' => $app] = $this->bootWithEnv(['APP_ENV' => 'production', 'APP_DEBUG' => 'false']);

    $request = (new \Slim\Psr7\Factory\ServerRequestFactory())
      ->createServerRequest('OPTIONS', '/api/products')
      ->withHeader('Origin', 'http://localhost:3000')
      ->withHeader('Access-Control-Request-Method', 'GET');

    $response = $app->handle($request);

    $this->assertSame(204, $response->getStatusCode());
    $this->assertSame('http://localhost:3000', $response->getHeaderLine('Access-Control-Allow-Origin'));
  }
}
