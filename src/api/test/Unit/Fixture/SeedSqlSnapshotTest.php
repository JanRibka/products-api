<?php

declare(strict_types=1);

namespace Test\Unit\Fixture;

use App\Fixture\FixtureData;
use PHPUnit\Framework\TestCase;

/**
 * The database/seed.sql snapshot is generated from FixtureData. These checks
 * make sure the committed snapshot still describes the current fixture data,
 * so the two sources can never silently drift apart.
 */
class SeedSqlSnapshotTest extends TestCase
{
  private const SEED_SQL = __DIR__ . '/../../../../../database/seed.sql';

  private function seedSql(): string
  {
    $sql = file_get_contents(self::SEED_SQL);
    self::assertIsString($sql, 'database/seed.sql must exist and be readable');

    return $sql;
  }

  public function testSeedSqlExists(): void
  {
    $this->assertFileExists(self::SEED_SQL);
  }

  public function testSeedSqlMentionsEveryBrand(): void
  {
    $sql = $this->seedSql();
    foreach (FixtureData::get()['brands'] as $brand) {
      $this->assertStringContainsString(
        "'" . $brand . "'",
        $sql,
        "Brand '$brand' is missing from database/seed.sql (run: php tracker fixtures:dump-sql)"
      );
    }
  }

  public function testSeedSqlMentionsEveryMaterial(): void
  {
    $sql = $this->seedSql();
    foreach (FixtureData::get()['materials'] as $material) {
      $this->assertStringContainsString(
        "'" . $material . "'",
        $sql,
        "Material '$material' is missing from database/seed.sql"
      );
    }
  }

  public function testSeedSqlMentionsEveryProductCode(): void
  {
    $sql = $this->seedSql();
    foreach (FixtureData::get()['products'] as $product) {
      $this->assertStringContainsString(
        "'" . $product['code'] . "'",
        $sql,
        "Product '{$product['code']}' is missing from database/seed.sql"
      );
    }
  }

  public function testSeedSqlProductRowCountMatchesFixtures(): void
  {
    $sql = $this->seedSql();

    // Each product row ends with "NOW(), NOW()),". Count them (the last row
    // uses ");" instead, so count the brand-style ")" of the VALUES list end).
    $rowCount = substr_count($sql, 'NOW(), NOW())');
    $this->assertSame(count(FixtureData::get()['products']), $rowCount);
  }

  public function testSeedSqlHasNoUnexpectedProductCodes(): void
  {
    $sql = $this->seedSql();
    $expected = array_column(FixtureData::get()['products'], 'code');

    // Only look at the product INSERT (brand/material rows share the format).
    $productPart = substr($sql, (int) strpos($sql, 'INSERT INTO `product`'));
    preg_match_all("/^\s*\('([^']+)'/m", $productPart, $matches);
    $found = $matches[1];

    sort($expected);
    sort($found);
    $this->assertSame($expected, $found, 'seed.sql contains codes not present in the fixtures');
  }
}
