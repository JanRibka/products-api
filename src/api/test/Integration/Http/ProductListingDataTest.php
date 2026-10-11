<?php

declare(strict_types=1);

namespace Test\Integration\Http;

use App\Fixture\FixtureData;
use Test\Integration\IntegrationTestCase;

/**
 * Deliberate assertions that use the real seed data (24 products, 7 brands,
 * 3 materials, Hliník unused).
 *
 * Covers:
 *  - price ties walked across every page (no duplicates / no missing) and the
 *    pages equal the CSV export,
 *  - pagination of 24 records (10, 10, 4, page 4 empty),
 *  - filter by brand, by material and by price range with exact result sets,
 *  - filter Hliník returns an empty page (total 0, totalPages 0) and its
 *    export is a header-only CSV.
 */
class ProductListingDataTest extends IntegrationTestCase
{
  public const TOTAL = 24;
  public const PER_PAGE = 10;

  /**
   * Walk every page for a query and return the rows in page order.
   *
   * @return array<int, array<string, mixed>>
   */
  private function collectAllRows(array $query): array
  {
    $rows = [];
    for ($page = 1; $page <= 4; $page++) {
      $body = $this->json($this->get(
        '/api/products?' . http_build_query(array_merge($query, ['page' => (string) $page]))
      ));
      foreach ($body['data'] as $row) {
        $rows[] = $row;
      }
      if ($page >= (int) $body['meta']['totalPages']) {
        break;
      }
    }

    return $rows;
  }

  /**
   * @return string[]
   */
  private function exportCodes(array $query = []): array
  {
    $response = $this->get('/api/products/export' . ($query === [] ? '' : '?' . http_build_query($query)));
    $this->assertSame(200, $response->getStatusCode());

    $content = (string) $response->getBody();
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
    $content = trim($content);

    if ($content === '') {
      return [];
    }

    $lines = preg_split('/\r\n|\n|\r/', $content) ?: [];
    $codes = [];
    foreach ($lines as $index => $line) {
      if ($index === 0) {
        continue; // header
      }
      $codes[] = str_getcsv($line, ';', '"', '')[0] ?? '';
    }

    return $codes;
  }

  public function testFirstPageMetaDescribesTheWholeDataSet(): void
  {
    $body = $this->json($this->get('/api/products'));

    $this->assertSame(self::TOTAL, $body['meta']['total']);
    $this->assertSame(self::PER_PAGE, $body['meta']['perPage']);
    $this->assertSame(1, $body['meta']['page']);
    $this->assertSame(3, $body['meta']['totalPages']);
    $this->assertCount(self::PER_PAGE, $body['data']);
  }

  public function testPaginationSplits24RecordsInto10And10And4AndPage4IsEmpty(): void
  {
    $page1 = $this->json($this->get('/api/products?page=1'));
    $page2 = $this->json($this->get('/api/products?page=2'));
    $page3 = $this->json($this->get('/api/products?page=3'));
    $page4 = $this->json($this->get('/api/products?page=4'));

    $this->assertCount(10, $page1['data']);
    $this->assertCount(10, $page2['data']);
    $this->assertCount(4, $page3['data']);
    $this->assertSame([], $page4['data']);

    // meta is identical across pages (it describes the whole result set).
    foreach ([$page1, $page2, $page3, $page4] as $page) {
      $this->assertSame(self::TOTAL, $page['meta']['total']);
      $this->assertSame(3, $page['meta']['totalPages']);
    }

    // Concatenation of the three non-empty pages is the full set, no dupes.
    $codes = array_merge(
      array_column($page1['data'], 'code'),
      array_column($page2['data'], 'code'),
      array_column($page3['data'], 'code')
    );
    $this->assertCount(self::TOTAL, $codes);
    $this->assertCount(self::TOTAL, array_unique($codes));
  }

  public function testPaginationOfASingleRecordStillReturnsOnePage(): void
  {
    $body = $this->json($this->get('/api/products?brandId=7'));

    $this->assertSame(1, $body['meta']['total']);
    $this->assertSame(1, $body['meta']['totalPages']);
    $this->assertSame('MM04453', $body['data'][0]['code']);

    $page2 = $this->json($this->get('/api/products?brandId=7&page=2'));
    $this->assertSame([], $page2['data']);
  }

  #[\PHPUnit\Framework\Attributes\DataProvider('priceOrderProvider')]
  public function testPriceTiesWalkAllPagesWithoutDuplicatesOrMissingAndMatchExport(string $order): void
  {
    $query = ['sort' => 'price', 'order' => $order];

    $rows = $this->collectAllRows($query);
    $codes = array_map(fn(array $row) => $row['code'], $rows);

    $this->assertCount(self::TOTAL, $codes, 'All 24 records must be present');
    $this->assertSame($codes, array_values(array_unique($codes)), 'No duplicates across pages');

    // Equal to the CSV export in the same order.
    $this->assertSame($codes, $this->exportCodes($query));

    // Prices are non-decreasing (asc) / non-increasing (desc) across pages.
    $prices = array_map(fn(array $row) => (float) $row['price'], $rows);
    $sorted = $prices;
    sort($sorted);
    if ($order === 'desc') {
      $sorted = array_reverse($sorted);
    }
    $this->assertSame($sorted, $prices);
  }

  /**
   * @return array<string, array{string}>
   */
  public static function priceOrderProvider(): array
  {
    return [
      'ascending' => ['asc'],
      'descending' => ['desc'],
    ];
  }

  public function testPriceTiesAreStableAcrossPages(): void
  {
    // 968.00 belongs to two records (PM00431, PM00432) and 1331.00 to three
    // (PM03448, PX00063, PX00065). The ORDER BY is "price <dir>, p.id ASC":
    // only the price direction flips, the id tie-breaker stays ascending, so
    // the members of a tied group keep the same relative order in both
    // directions and nothing is dropped or duplicated across page boundaries.
    $ascOrdered = array_map(
      fn(array $row) => $row['code'],
      $this->collectAllRows(['sort' => 'price', 'order' => 'asc'])
    );
    $descOrdered = array_map(
      fn(array $row) => $row['code'],
      $this->collectAllRows(['sort' => 'price', 'order' => 'desc'])
    );

    // Same multiset of records in both directions.
    $ascSorted = $ascOrdered;
    $descSorted = $descOrdered;
    sort($ascSorted);
    sort($descSorted);
    $this->assertSame($ascSorted, $descSorted);

    // The 968.00 group comes first in ascending order and the members are
    // adjacent and ordered by id.
    $this->assertSame(['PM00431', 'PM00432'], array_slice($ascOrdered, 0, 2));

    // The 1331.00 group's members are adjacent and keep the same id-based
    // order in both directions.
    $this->assertAdjacentGroup($ascOrdered, ['PM03448', 'PX00063', 'PX00065']);
    $this->assertAdjacentGroup($descOrdered, ['PM03448', 'PX00063', 'PX00065']);

    // Sanity: the two tied prices really occur in the seed data.
    $prices = array_column(FixtureData::get()['products'], 'price');
    $this->assertSame(2, count(array_keys($prices, '968.00')));
    $this->assertSame(3, count(array_keys($prices, '1331.00')));
  }

  /**
   * Assert that a group of codes appears as one contiguous, in-order block.
   *
   * @param string[] $codes
   * @param string[] $group
   */
  private function assertAdjacentGroup(array $codes, array $group): void
  {
    $start = array_search($group[0], $codes, true);
    $this->assertNotFalse($start, implode(',', $group) . ' not found');
    $this->assertSame(
      $group,
      array_slice($codes, (int) $start, count($group)),
      'Tied group is not adjacent/in order'
    );
  }

  public function testFilterByBrandReturnsExactlyThatBrandsProducts(): void
  {
    // brandId 1 = Škoda (5 products).
    $body = $this->json($this->get('/api/products?brandId=1&sort=code&order=asc'));

    $this->assertSame(5, $body['meta']['total']);
    $this->assertSame(
      ['MM00904', 'MX04548', 'PM00431', 'PM00433', 'PM03448'],
      array_column($body['data'], 'code')
    );
    foreach ($body['data'] as $row) {
      $this->assertSame('Škoda', $row['brandName']);
    }
  }

  public function testFilterByMaterialReturnsExactlyThatMaterialsProducts(): void
  {
    // materialId 1 = Plast (7 products).
    $body = $this->json($this->get('/api/products?materialId=1&sort=code&order=asc'));

    $this->assertSame(7, $body['meta']['total']);
    $this->assertSame(
      ['PM00412', 'PM00431', 'PM00432', 'PM00433', 'PM03448', 'PX00063', 'PX00065'],
      array_column($body['data'], 'code')
    );
    foreach ($body['data'] as $row) {
      $this->assertSame('Plast', $row['materialName']);
    }
  }

  public function testFilterByPriceRangeReturnsExactlyTheExpectedSet(): void
  {
    $body = $this->json($this->get('/api/products?priceFrom=4000&priceTo=5000&sort=price&order=asc'));

    $this->assertSame(7, $body['meta']['total']);
    $this->assertSame(
      ['MM00587', 'MM04460', 'MM04459', 'MX04509', 'MX04548', 'MM04452', 'MM04453'],
      array_column($body['data'], 'code')
    );

    $prices = array_map(fn(array $row) => (float) $row['price'], $body['data']);
    $this->assertSame([4152.0, 4205.0, 4296.0, 4326.0, 4459.0, 4840.0, 4901.0], $prices);
  }

  public function testFilterByHlinikReturnsEmptyListAndEmptyMeta(): void
  {
    // materialId 3 = Hliník, which deliberately has no products.
    $body = $this->json($this->get('/api/products?materialId=3'));

    $this->assertSame([], $body['data']);
    $this->assertSame(0, $body['meta']['total']);
    $this->assertSame(0, $body['meta']['totalPages']);
  }

  public function testHlinikExportIsHeaderOnly(): void
  {
    $response = $this->get('/api/products/export?materialId=3');
    $this->assertSame(200, $response->getStatusCode());
    $this->assertStringContainsString('text/csv', $response->getHeaderLine('Content-Type'));

    $content = (string) $response->getBody();
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

    // Exactly one (header) line, no data rows.
    $lines = array_values(array_filter(
      preg_split('/\r\n|\n|\r/', trim($content)) ?: [],
      fn($l) => $l !== ''
    ));
    $this->assertCount(1, $lines);
    $this->assertSame('Kód;Název;Popis;Cena;Značka;Materiál', $lines[0]);
    $this->assertSame([], $this->exportCodes(['materialId' => '3']));
  }

  public function testFullExportHasHeaderPlusAll24Products(): void
  {
    $codes = $this->exportCodes();

    $this->assertCount(self::TOTAL, $codes);
    $this->assertSame($codes, array_values(array_unique($codes)));
  }

  public function testFixtureDataMatchesTheLoadedDatabase(): void
  {
    // The test seed loads FixtureData verbatim, so the API must expose all of
    // its codes.
    $data = FixtureData::get();
    $expectedCodes = array_column($data['products'], 'code');

    $actualCodes = $this->exportCodes();

    sort($expectedCodes);
    sort($actualCodes);
    $this->assertSame($expectedCodes, $actualCodes);
    $this->assertCount(24, $data['products']);
    $this->assertCount(7, $data['brands']);
    $this->assertCount(3, $data['materials']);
  }
}
