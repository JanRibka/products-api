<?php

declare(strict_types=1);

namespace Test\Unit\Shared;

use PHPUnit\Framework\TestCase;

/**
 * M3: nginx must not implement CORS at all - the application's CorsMiddleware
 * with the ALLOWED_ORIGINS allowlist is the single source of truth. Reflecting
 * $http_origin in nginx would bypass that allowlist.
 *
 * The nginx config is a template mounted into the official nginx image, which
 * renders it through envsubst. Only defined environment variables are
 * substituted, so nginx runtime variables stay intact.
 */
class NginxConfigTest extends TestCase
{
  private function nginxConfig(): string
  {
    // test/Unit/Shared -> src/api -> repository root (api/) -> docker/
    $path = dirname(__DIR__, 5) . '/docker/nginx/nginx.conf.template';
    $content = file_get_contents($path);

    $this->assertNotFalse($content, "nginx.conf.template not found at $path");

    return (string) $content;
  }

  public function testNginxDoesNotReflectOrigin(): void
  {
    $this->assertStringNotContainsString('$http_origin', $this->nginxConfig());
  }

  public function testNginxDeclaresNoCorsHeaders(): void
  {
    $config = $this->nginxConfig();

    $forbidden = [
      'Access-Control-Allow-Origin',
      'Access-Control-Allow-Methods',
      'Access-Control-Allow-Headers',
      'Access-Control-Allow-Credentials',
      'Access-Control-Max-Age',
      'Access-Control-Expose-Headers',
    ];

    foreach ($forbidden as $header) {
      $this->assertStringNotContainsString($header, $config, "$header must be handled by the app, not nginx");
    }
  }

  public function testRedirectKeepsTheNonDefaultHttpsPort(): void
  {
    $config = $this->nginxConfig();

    $this->assertMatchesRegularExpression(
      '/return\s+301\s+https:\/\/\$host:\$\{API_HTTPS_PORT\}\$request_uri\s*;/',
      $config,
      'The HTTP->HTTPS redirect must include the ${API_HTTPS_PORT} placeholder so it keeps the published HTTPS port.'
    );

    // Guard against a bare redirect that would drop the port and send the
    // browser to :443 while HTTPS is published elsewhere (default 3444).
    $this->assertStringNotContainsString('https://$host$request_uri', $config);
  }
}
