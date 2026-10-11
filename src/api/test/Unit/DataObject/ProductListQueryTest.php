<?php

declare(strict_types=1);

namespace Test\Unit\DataObject;

use App\DataObject\Data\ProductListQuery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProductListQueryTest extends TestCase
{
  public function testDefaultsWhenNoParamsGiven(): void
  {
    $query = ProductListQuery::fromArray([]);

    $this->assertSame(1, $query->page);
    $this->assertSame('code', $query->sort);
    $this->assertSame('asc', $query->order);
    $this->assertNull($query->brandId);
    $this->assertNull($query->materialId);
    $this->assertNull($query->priceFrom);
    $this->assertNull($query->priceTo);
    $this->assertSame('', $query->search);
  }

  public function testPageBelowOneFallsBackToOne(): void
  {
    $this->assertSame(1, ProductListQuery::fromArray(['page' => '0'])->page);
    $this->assertSame(1, ProductListQuery::fromArray(['page' => '-5'])->page);
    $this->assertSame(1, ProductListQuery::fromArray(['page' => 'abc'])->page);
    $this->assertSame(3, ProductListQuery::fromArray(['page' => '3'])->page);
  }

  #[DataProvider('sortWhitelistProvider')]
  public function testSortOnlyAllowsWhitelistedColumns(string $input, string $expected): void
  {
    $this->assertSame($expected, ProductListQuery::fromArray(['sort' => $input])->sort);
  }

  public static function sortWhitelistProvider(): array
  {
    return [
      'valid code' => ['code', 'code'],
      'valid name' => ['name', 'name'],
      'valid price' => ['price', 'price'],
      'valid brand' => ['brand', 'brand'],
      'valid material' => ['material', 'material'],
      'invalid drops to default' => ['id; DROP TABLE product', 'code'],
      'empty drops to default' => ['', 'code'],
    ];
  }

  #[DataProvider('orderProvider')]
  public function testOrderIsRestrictedToAscDesc(string $input, string $expected): void
  {
    $this->assertSame($expected, ProductListQuery::fromArray(['order' => $input])->order);
  }

  public static function orderProvider(): array
  {
    return [
      'asc' => ['asc', 'asc'],
      'ASC lowercased' => ['ASC', 'asc'],
      'desc' => ['desc', 'desc'],
      'invalid defaults to asc' => ['sideways', 'asc'],
      'sql injection defaults to asc' => ['asc; DROP TABLE', 'asc'],
    ];
  }

  public function testSortColumnMapping(): void
  {
    $this->assertSame('p.code', ProductListQuery::fromArray(['sort' => 'code'])->getSortColumn());
    $this->assertSame('p.name', ProductListQuery::fromArray(['sort' => 'name'])->getSortColumn());
    $this->assertSame('p.price', ProductListQuery::fromArray(['sort' => 'price'])->getSortColumn());
    $this->assertSame('b.name', ProductListQuery::fromArray(['sort' => 'brand'])->getSortColumn());
    $this->assertSame('m.name', ProductListQuery::fromArray(['sort' => 'material'])->getSortColumn());
  }

  public function testOrderSql(): void
  {
    $this->assertSame('ASC', ProductListQuery::fromArray(['order' => 'asc'])->getOrder());
    $this->assertSame('DESC', ProductListQuery::fromArray(['order' => 'desc'])->getOrder());
  }

  public function testOffset(): void
  {
    $this->assertSame(0, ProductListQuery::fromArray(['page' => '1'])->getOffset());
    $this->assertSame(10, ProductListQuery::fromArray(['page' => '2'])->getOffset());
    $this->assertSame(20, ProductListQuery::fromArray(['page' => '3'])->getOffset());
  }

  public function testNumericFiltersAreParsed(): void
  {
    $query = ProductListQuery::fromArray([
      'brandId' => '2',
      'materialId' => '3',
      'priceFrom' => '100',
      'priceTo' => '2000.50',
    ]);

    $this->assertSame(2, $query->brandId);
    $this->assertSame(3, $query->materialId);
    $this->assertSame('100', $query->priceFrom);
    $this->assertSame('2000.50', $query->priceTo);
  }

  public function testInvalidNumericFiltersBecomeNull(): void
  {
    $query = ProductListQuery::fromArray([
      'brandId' => 'abc',
      'materialId' => '0',
      'priceFrom' => 'not-a-price',
      'priceTo' => '',
    ]);

    $this->assertNull($query->brandId);
    $this->assertNull($query->materialId);
    $this->assertNull($query->priceFrom);
    $this->assertNull($query->priceTo);
  }

  public function testSearchIsTrimmed(): void
  {
    $this->assertSame('audi', ProductListQuery::fromArray(['search' => '  audi  '])->search);
  }

  public function testSearchNonStringBecomesEmpty(): void
  {
    $this->assertSame('', ProductListQuery::fromArray(['search' => ['a', 'b']])->search);
  }

  public function testGetFiltersShape(): void
  {
    $query = ProductListQuery::fromArray(['brandId' => '1', 'search' => 'kryt']);

    $this->assertSame(
      [
        'brandId' => 1,
        'materialId' => null,
        'priceFrom' => null,
        'priceTo' => null,
        'search' => 'kryt',
      ],
      $query->getFilters()
    );
  }
}
