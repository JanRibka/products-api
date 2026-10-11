<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;

/**
 * Application-level CORS handling.
 *
 * Handled as PSR-15 middleware (instead of raw header()/exit() calls) so the
 * ALLOWED_ORIGINS allowlist is authoritative and works in every environment,
 * including production, and is covered by the integration tests.
 */
class CorsMiddleware implements MiddlewareInterface
{
  private const ALLOWED_METHODS = 'GET, POST, PUT, DELETE, OPTIONS';
  private const ALLOWED_HEADERS = 'Origin, Content-Type, Accept, Authorization';

  /** @var string[] */
  private array $allowedOrigins;

  public function __construct(Config $config)
  {
    /** @var string[] $origins */
    $origins = $config->get('allowed_origins', []);
    $this->allowedOrigins = $origins;
  }

  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
    $origin = $request->getHeaderLine('Origin');
    $hasOrigin = $origin !== '';
    $isAllowed = $hasOrigin && in_array($origin, $this->allowedOrigins, true);

    $isPreflight = $request->getMethod() === 'OPTIONS'
      && $request->hasHeader('Access-Control-Request-Method');

    if ($isPreflight) {
      $response = (new ResponseFactory())->createResponse(204);
    } else {
      $response = $handler->handle($request);
    }

    // A disallowed (or absent) origin receives no CORS response headers at all.
    if (!$isAllowed) {
      return $response;
    }

    $response = $response
      ->withHeader('Access-Control-Allow-Origin', $origin)
      ->withHeader('Access-Control-Allow-Credentials', 'true')
      ->withHeader('Access-Control-Allow-Methods', self::ALLOWED_METHODS)
      ->withHeader('Access-Control-Allow-Headers', self::ALLOWED_HEADERS)
      ->withAddedHeader('Vary', 'Origin');

    if ($isPreflight) {
      $response = $response->withHeader('Access-Control-Max-Age', '1728000');
    }

    return $response;
  }
}
