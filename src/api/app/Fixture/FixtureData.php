<?php

declare(strict_types=1);

namespace App\Fixture;

/**
 * Central access point for the fixture seed data.
 *
 * The actual data lives in a single PHP array in app/Fixture/data/products.php
 * and is loaded once. Fixture classes, tests and the database/seed.sql snapshot
 * all derive from this, so there is exactly one source of truth.
 */
final class FixtureData
{
  /**
   * @var array{brands: string[], materials: string[],
   *   products: array<int, array{code: string, name: string, description: string,
   *   price: string, brand: string, material: string}>}|null
   */
  private static ?array $data = null;

  /**
   * @return array{brands: string[], materials: string[],
   *   products: array<int, array{code: string, name: string, description: string,
   *   price: string, brand: string, material: string}>}
   */
  public static function get(): array
  {
    if (self::$data === null) {
      /** @var array{brands: string[], materials: string[],
       *   products: array<int, array{code: string, name: string, description: string,
       *   price: string, brand: string, material: string}>} $data */
      $data = require __DIR__ . '/data/products.php';
      self::$data = $data;
    }

    return self::$data;
  }
}
