<?php

declare(strict_types=1);

namespace Test\Unit\Shared;

use App\Entity\Brand\Implementation\Brand;
use App\Entity\Material\Implementation\Material;
use App\Entity\Product\Implementation\Product;
use App\Shared\CsvExporter;
use PHPUnit\Framework\TestCase;

/**
 * M2: CSV export hardening.
 *
 * - text columns are guarded against spreadsheet formula injection,
 * - the price column is left untouched,
 * - rows round-trip through a real CSV reader (delimiter, quotes, backslashes,
 *   newlines, diacritics).
 */
class CsvExporterTest extends TestCase
{
  private CsvExporter $exporter;

  protected function setUp(): void
  {
    $this->exporter = new CsvExporter();
  }

  private function product(
    string $code,
    string $name,
    ?string $description,
    string $price,
    string $brand,
    string $material
  ): Product {
    $brandEntity = (new Brand())->setName($brand);
    $materialEntity = (new Material())->setName($material);

    return (new Product())
      ->setCode($code)
      ->setName($name)
      ->setDescription($description)
      ->setPrice($price)
      ->setBrand($brandEntity)
      ->setMaterial($materialEntity)
      ->setCreatedAt()
      ->setUpdatedAt();
  }

  /**
   * @return resource
   */
  private function exportStream(array $products)
  {
    $stream = fopen('php://temp', 'r+');
    $this->exporter->writeBom($stream);
    $this->exporter->writeRow($stream, $this->exporter->getHeader());
    foreach ($products as $product) {
      $this->exporter->writeRow($stream, $this->exporter->productToRow($product));
    }
    rewind($stream);

    return $stream;
  }

  /**
   * @return array<int, array<int, string>>
   */
  private function parseBack(array $products): array
  {
    $stream = $this->exportStream($products);
    $content = (string) stream_get_contents($stream);
    fclose($stream);

    // Strip BOM before parsing.
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

    $rows = [];
    $reader = fopen('php://temp', 'r+');
    fwrite($reader, $content);
    rewind($reader);
    while (($row = fgetcsv($reader, 0, ';', '"', '')) !== false) {
      $rows[] = $row;
    }
    fclose($reader);

    return $rows;
  }

  public function testFormulaPrefixesInTextColumnsAreEscapedWithSingleQuote(): void
  {
    $product = $this->product(
      '=1+1',
      '+SUM(A1)',
      '@cmd',
      '100.00',
      '-Ford',
      '=Material'
    );

    $rows = $this->parseBack([$product]);
    $row = $rows[1];

    $this->assertSame("'=1+1", $row[0], 'code');
    $this->assertSame("'+SUM(A1)", $row[1], 'name');
    $this->assertSame("'@cmd", $row[2], 'description');
    $this->assertSame('100.00', $row[3], 'price must NOT be altered');
    $this->assertSame("'-Ford", $row[4], 'brand');
    $this->assertSame("'=Material", $row[5], 'material');
  }

  public function testFormulaPrefixAfterLeadingWhitespaceTabOrCrIsEscaped(): void
  {
    $product = $this->product(
      'code',
      " \t=evil",
      "=evil",
      '100.00',
      " \n-Audi",
      "\r@x"
    );

    $rows = $this->parseBack([$product]);
    $row = $rows[1];

    $this->assertSame("' \t=evil", $row[1], 'leading space/tab then formula');
    $this->assertSame("'=evil", $row[2]);
    $this->assertSame("' \n-Audi", $row[4]);
    $this->assertSame("'\r@x", $row[5]);
  }

  public function testPlainValuesAreNotAltered(): void
  {
    $product = $this->product('PM00001', 'Kryt Audi A4', 'Popis', '1890.00', 'Audi', 'Plast');

    $rows = $this->parseBack([$product]);
    $row = $rows[1];

    $this->assertSame('PM00001', $row[0]);
    $this->assertSame('Kryt Audi A4', $row[1]);
    $this->assertSame('Popis', $row[2]);
    $this->assertSame('1890.00', $row[3]);
    $this->assertSame('Audi', $row[4]);
    $this->assertSame('Plast', $row[5]);
  }

  public function testPriceWithMinusSignIsNotEscaped(): void
  {
    // Even a price that would look like a formula must be left as-is.
    $product = $this->product('PM00002', 'Nazev', 'Popis', '-1234.56', 'Audi', 'Plast');

    $rows = $this->parseBack([$product]);

    $this->assertSame('-1234.56', $rows[1][3]);
  }

  public function testSpecialCharactersRoundTripThroughCsvReader(): void
  {
    $code = 'PM;00"1';
    $name = "Nazev s \"uvozovkami\" a \\\\ zpetnym lomitem";
    $description = "Radek 1\nRadek 2; s středníkem";
    $brand = 'Škoda';
    $material = 'Hliník';

    $product = $this->product($code, $name, $description, '99.00', $brand, $material);

    $rows = $this->parseBack([$product]);
    $row = $rows[1];

    $this->assertSame($code, $row[0]);
    $this->assertSame($name, $row[1]);
    $this->assertSame($description, $row[2]);
    $this->assertSame('99.00', $row[3]);
    $this->assertSame('Škoda', $row[4]);
    $this->assertSame('Hliník', $row[5]);
  }

  public function testRowCountMatchesInputWithLineBreaksInValues(): void
  {
    $products = [
      $this->product('PM00001', "A\nB", "C\nD", '10.00', 'Audi', 'Plast'),
      $this->product('PM00002', 'E', 'F', '20.00', 'BMW', 'Plech'),
    ];

    $rows = $this->parseBack($products);

    // header + 2 data rows (embedded newlines must not create extra rows)
    $this->assertCount(3, $rows);
  }

  public function testBomIsWrittenFirst(): void
  {
    $stream = $this->exportStream([]);
    $content = (string) stream_get_contents($stream);
    fclose($stream);

    $this->assertStringStartsWith(CsvExporter::BOM, $content);
  }
}
