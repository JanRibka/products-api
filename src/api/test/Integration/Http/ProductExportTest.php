<?php

declare(strict_types=1);

namespace Test\Integration\Http;

use App\Entity\Product\Implementation\Product;
use Test\Integration\IntegrationTestCase;

/**
 * M2: the export endpoint must apply the CSV hardening end-to-end and the
 * resulting file must round-trip through a real CSV reader.
 */
class ProductExportTest extends IntegrationTestCase
{
  /**
   * Insert one product with hostile values on top of the standard seed.
   */
  private function seedHostileProduct(): void
  {
    $brand = $this->entityManager->getRepository(\App\Entity\Brand\Implementation\Brand::class)
      ->find($this->brandIds['Škoda']);
    $material = $this->entityManager->getRepository(\App\Entity\Material\Implementation\Material::class)
      ->find($this->materialIds['Hliník']);

    $product = new Product();
    $product
      ->setCode('=1+1')
      ->setName("=SUM(A1); \"uvozovky\" \\\\ lomítko")
      ->setDescription("@cmd\nRadek 2 s středníkem")
      ->setPrice('999.00')
      ->setBrand($brand)
      ->setMaterial($material)
      ->setCreatedAt()
      ->setUpdatedAt();

    $this->entityManager->persist($product);
    $this->entityManager->flush();
    $this->entityManager->clear();
  }

  /**
   * @return array<int, array<int, string>>
   */
  private function fetchExportRows(): array
  {
    $response = $this->get('/api/products/export');
    $this->assertSame(200, $response->getStatusCode());
    $this->assertStringContainsString('text/csv', $response->getHeaderLine('Content-Type'));

    $content = (string) $response->getBody();
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

    $stream = fopen('php://temp', 'r+');
    fwrite($stream, $content);
    rewind($stream);

    $rows = [];
    while (($row = fgetcsv($stream, 0, ';', '"', '')) !== false) {
      $rows[] = $row;
    }
    fclose($stream);

    return $rows;
  }

  public function testExportGuardsFormulaInjectionButLeavesPriceUntouched(): void
  {
    $this->seedHostileProduct();

    $rows = $this->fetchExportRows();
    // header + 24 seeded products + 1 hostile product
    $this->assertCount(26, $rows);

    $hostile = null;
    foreach (array_slice($rows, 1) as $row) {
      if ($row[0] === "'=1+1") {
        $hostile = $row;

        break;
      }
    }

    $this->assertNotNull($hostile, "Hostile row not found; codes: " . json_encode(array_column($rows, 0)));
    $this->assertSame("'=1+1", $hostile[0], 'code escaped');
    $this->assertStringStartsWith("'=SUM(A1)", $hostile[1], 'name escaped');
    $this->assertStringStartsWith("'@cmd", $hostile[2], 'description escaped');
    $this->assertSame('999.00', $hostile[3], 'price untouched');
  }

  public function testExportRoundTripsDiacriticsAndQuotes(): void
  {
    $rows = $this->fetchExportRows();

    // Header row carries diacritics untouched.
    $this->assertSame(['Kód', 'Název', 'Popis', 'Cena', 'Značka', 'Materiál'], $rows[0]);

    // Every data row has exactly 6 columns (parser did not split on "; " inside text).
    foreach (array_slice($rows, 1) as $row) {
      $this->assertCount(6, $row);
    }

    $brands = array_column(array_slice($rows, 1), 4);
    $this->assertContains('Škoda', $brands);
  }

  public function testExportFieldsContainingDelimiterAreQuoted(): void
  {
    $this->seedHostileProduct();

    $rows = $this->fetchExportRows();

    // The reader already un-quoted the fields; assert the delimiter survived.
    $hostile = array_values(array_filter(
      array_slice($rows, 1),
      fn(array $row) => $row[0] === "'=1+1"
    ))[0];

    $this->assertStringContainsString(';', $hostile[1]);
    $this->assertStringContainsString('"uvozovky"', $hostile[1]);
  }
}
