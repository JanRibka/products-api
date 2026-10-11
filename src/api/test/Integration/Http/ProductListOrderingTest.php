<?php

declare(strict_types=1);

namespace Test\Integration\Http;

use Test\Integration\IntegrationTestCase;

/**
 * B2: ordering must be deterministic across pages and equal to the CSV export.
 *
 * Every sort column is walked asc/desc, unfiltered and with brand/material/
 * price filters. Price deliberately contains ties (968 twice, 1331 three
 * times); the p.id tie-breaker must keep the order stable so no record is
 * duplicated or dropped across page boundaries and the pages match the export.
 */
class ProductListOrderingTest extends IntegrationTestCase
{
  private const SORT_COLUMNS = ['code', 'name', 'price', 'brand', 'material'];
  private const ORDERS = ['asc', 'desc'];
  private const PRICE_FROM = '900';
  private const PRICE_TO = '5000';

  /**
   * @return array<int, array{sort: string, order: string, filters: array<string,string>}>
   */
  public static function orderingScenarios(): array
  {
    $scenarios = [];

    foreach (self::SORT_COLUMNS as $sort) {
      foreach (self::ORDERS as $order) {
        $scenarios["$sort $order"] = ['sort' => $sort, 'order' => $order, 'filters' => []];
        $scenarios["$sort $order filtered by brand"] = [
          'sort' => $sort,
          'order' => $order,
          'filters' => ['brandId' => '1'],
        ];
        $scenarios["$sort $order filtered by material"] = [
          'sort' => $sort,
          'order' => $order,
          'filters' => ['materialId' => '1'],
        ];
        $scenarios["$sort $order price range"] = [
          'sort' => $sort,
          'order' => $order,
          'filters' => ['priceFrom' => self::PRICE_FROM, 'priceTo' => self::PRICE_TO],
        ];
      }
    }

    return $scenarios;
  }

  #[\PHPUnit\Framework\Attributes\DataProvider('orderingScenarios')]
  public function testPagesHaveNoDuplicatesNoMissingAndMatchExport(string $sort, string $order, array $filters): void
  {
    $query = array_merge(['sort' => $sort, 'order' => $order], $filters);

    $listCodes = $this->collectAllPages($query);
    $expectedTotal = $this->firstPageMeta($query)['total'];

    // No duplicates in the concatenation of all pages.
    $this->assertSame(
      count($listCodes),
      count(array_unique($listCodes)),
      "Duplicates across pages for sort=$sort order=$order filters=" . json_encode($filters)
    );

    // No missing records.
    $this->assertCount(
      $expectedTotal,
      $listCodes,
      "Missing records for sort=$sort order=$order filters=" . json_encode($filters)
    );

    // Equal to the CSV export for the same parameters.
    $exportCodes = $this->exportCodes($query);
    $this->assertSame(
      $listCodes,
      $exportCodes,
      "List pages differ from CSV export for sort=$sort order=$order filters=" . json_encode($filters)
    );
  }

  #[\PHPUnit\Framework\Attributes\DataProvider('orderingScenarios')]
  public function testOrderingIsAlignedWithSortColumn(string $sort, string $order, array $filters): void
  {
    $query = array_merge(['sort' => $sort, 'order' => $order], $filters);
    $rows = $this->collectAllRows($query);

    if (count($rows) < 2) {
      $this->assertTrue(true);

      return;
    }

    $keys = array_map(fn(array $row) => $this->sortKey($row, $sort), $rows);
    $sorted = $keys;
    sort($sorted);
    if ($order === 'desc') {
      $sorted = array_reverse($sorted);
    }
    // ignore the case where the key is null (not applicable here)
    $this->assertSame($sorted, $keys, "Rows are not sorted by $sort $order");
  }

  /**
   * Sort key used to model the database ordering in PHP.
   *
   * The database orders text columns with the utf8mb4_unicode_ci collation,
   * which is case- and accent-insensitive and is not the same as PHP's
   * byte-wise sort(). Folding the value the same way (lowercase + Czech
   * diacritics stripped) reproduces the database order for this dataset.
   */
  private function collationKey(string $value): string
  {
    $value = mb_strtolower($value, 'UTF-8');

    return strtr($value, [
      'á' => 'a',
      'ä' => 'a',
      'â' => 'a',
      'à' => 'a',
      'č' => 'c',
      'ć' => 'c',
      'ď' => 'd',
      'é' => 'e',
      'ě' => 'e',
      'ë' => 'e',
      'è' => 'e',
      'í' => 'i',
      'ì' => 'i',
      'ň' => 'n',
      'ń' => 'n',
      'ó' => 'o',
      'ö' => 'o',
      'ô' => 'o',
      'ř' => 'r',
      'š' => 's',
      'ś' => 's',
      'ť' => 't',
      'ú' => 'u',
      'ů' => 'u',
      'ü' => 'u',
      'ù' => 'u',
      'ý' => 'y',
      'ž' => 'z',
      'ź' => 'z',
    ]);
  }

  private function sortKey(array $row, string $sort): string
  {
    $value = match ($sort) {
      'code' => $row['code'],
      'name' => $row['name'],
      'price' => (string) $row['price'],
      'brand' => $row['brandName'],
      'material' => $row['materialName'],
      default => '',
    };

    // price is numeric; only text columns use the collation folding.
    return $sort === 'price' ? $value : $this->collationKey($value);
  }

  /**
   * @return string[]
   */
  private function collectAllPages(array $query): array
  {
    return array_map(fn(array $row) => $row['code'], $this->collectAllRows($query));
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  private function collectAllRows(array $query): array
  {
    $meta = $this->firstPageMeta($query);
    $totalPages = max(1, $meta['totalPages']);

    $rows = [];
    for ($page = 1; $page <= $totalPages; $page++) {
      $pageQuery = array_merge($query, ['page' => (string) $page]);
      $body = $this->json($this->get('/api/products?' . http_build_query($pageQuery)));
      foreach ($body['data'] as $row) {
        $rows[] = $row;
      }
    }

    return $rows;
  }

  /**
   * @return array{page:int, perPage:int, total:int, totalPages:int}
   */
  private function firstPageMeta(array $query): array
  {
    $body = $this->json($this->get('/api/products?' . http_build_query($query)));

    return $body['meta'];
  }

  /**
   * @return string[]
   */
  private function exportCodes(array $query): array
  {
    $response = $this->get('/api/products/export?' . http_build_query($query));
    $this->assertSame(200, $response->getStatusCode());

    $content = (string) $response->getBody();
    // Strip UTF-8 BOM.
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

    $lines = preg_split('/\r\n|\n|\r/', trim($content)) ?: [];
    $codes = [];
    foreach ($lines as $index => $line) {
      if ($index === 0 || $line === '') {
        continue; // header row
      }
      $fields = str_getcsv($line, ';', '"', '');
      $codes[] = $fields[0] ?? '';
    }

    return $codes;
  }
}
