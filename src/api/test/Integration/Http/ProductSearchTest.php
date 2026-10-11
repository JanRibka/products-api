<?php

declare(strict_types=1);

namespace Test\Integration\Http;

use Test\Integration\IntegrationTestCase;

/**
 * Search semantics: LIKE wildcards in the term are treated literally, and the
 * database collation (utf8mb4_unicode_ci) makes matching case- and
 * accent-insensitive.
 */
class ProductSearchTest extends IntegrationTestCase
{
  public function testSearchWildcardPercentMatchesLiterally(): void
  {
    $body = $this->json($this->get('/api/products?search=' . urlencode('%')));

    // No seeded product contains a literal "%", so a literal search must
    // return nothing instead of matching every row.
    $this->assertSame(0, $body['meta']['total']);
    $this->assertSame([], $body['data']);
  }

  public function testSearchWildcardUnderscoreMatchesLiterally(): void
  {
    $body = $this->json($this->get('/api/products?search=' . urlencode('_')));

    // A literal "_" must match only the two names that really contain an
    // underscore (VW Crafter "(SY_, SX_)" and Citroen C4 "(3D_)"), not every
    // single-character position.
    $codes = array_column($body['data'], 'code');
    sort($codes);
    $this->assertSame(2, $body['meta']['total']);
    $this->assertSame(['MM04458', 'MX04533'], $codes);
  }

  public function testSearchFindsSubstringOfAnExistingProduct(): void
  {
    $body = $this->json($this->get('/api/products?search=KODIAQ'));

    $this->assertSame(1, $body['meta']['total']);
    $this->assertSame('MX04548', $body['data'][0]['code']);
  }

  public function testSearchCanMatchByCode(): void
  {
    $body = $this->json($this->get('/api/products?search=MM04453'));

    $this->assertSame(1, $body['meta']['total']);
    $this->assertSame('MM04453', $body['data'][0]['code']);
  }

  /**
   * @return array<string, array{string}>
   */
  public static function diacriticScenarios(): array
  {
    return [
      'czech brand with diacritics as stored' => ['Škoda'],
      'brand without diacritics' => ['Skoda'],
      'brand upper case' => ['SKODA'],
      'citroen with diacritics' => ['Citroën'],
      'citroen without diacritics' => ['Citroen'],
      'citroen upper case' => ['CITROEN'],
    ];
  }

  #[\PHPUnit\Framework\Attributes\DataProvider('diacriticScenarios')]
  public function testSearchIsCaseAndAccentInsensitive(string $term): void
  {
    $body = $this->json($this->get('/api/products?search=' . urlencode($term)));

    $brandName = match (mb_strtolower($term, 'UTF-8')) {
      'škoda', 'skoda' => 'Škoda',
      'citroën', 'citroen' => 'Citroën',
      default => '',
    };

    $expected = 0;
    foreach (\App\Fixture\FixtureData::get()['products'] as $product) {
      if ($product['brand'] === $brandName) {
        $expected++;
      }
    }

    $this->assertGreaterThan(0, $expected, 'Fixture data should contain this brand');
    $this->assertSame($expected, $body['meta']['total']);
  }
}
