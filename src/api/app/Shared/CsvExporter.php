<?php

declare(strict_types=1);

namespace App\Shared;

use App\Entity\Product\Contract\ProductInterface;

class CsvExporter
{
  public const DELIMITER = ';';
  public const BOM = "\xEF\xBB\xBF";

  /**
   * Characters that make a spreadsheet treat a cell as a formula.
   */
  private const FORMULA_PREFIXES = ['=', '+', '-', '@'];

  /**
   * Header row for the products export.
   *
   * @return string[]
   */
  public function getHeader(): array
  {
    return ['Kód', 'Název', 'Popis', 'Cena', 'Značka', 'Materiál'];
  }

  /**
   * Map a product entity to a CSV row (values already formatted for output).
   *
   * Text columns are guarded against spreadsheet formula injection; the price
   * column is numeric output and is intentionally left untouched.
   *
   * @return array<int, string>
   */
  public function productToRow(ProductInterface $product): array
  {
    return [
      $this->guardFormula($product->getCode()),
      $this->guardFormula($product->getName()),
      $this->guardFormula($product->getDescription() ?? ''),
      $product->getPrice(),
      $this->guardFormula($product->getBrand()->getName()),
      $this->guardFormula($product->getMaterial()->getName()),
    ];
  }

  /**
   * Neutralize spreadsheet formula injection.
   *
   * A value that starts with = + - @ - optionally after leading whitespace,
   * tab or CR - is prefixed with a single quote so spreadsheets treat it as
   * literal text. The original value (including any leading whitespace) is
   * preserved after the quote.
   */
  public function guardFormula(string $value): string
  {
    if ($value === '') {
      return $value;
    }

    $trimmed = ltrim($value, " \t\r\n\v\f");

    if ($trimmed !== '' && in_array($trimmed[0], self::FORMULA_PREFIXES, true)) {
      return "'" . $value;
    }

    return $value;
  }

  /**
   * Write the UTF-8 BOM header to the given stream.
   *
   * @param resource $stream
   */
  public function writeBom($stream): void
  {
    fwrite($stream, self::BOM);
  }

  /**
   * Write a single CSV row (with delimiter and newline) to the given stream.
   *
   * The escape character is disabled (empty string) so backslashes are written
   * literally and round-trip correctly; quoting is handled by enclosure.
   *
   * @param resource $stream
   * @param array<int, string> $row
   */
  public function writeRow($stream, array $row): void
  {
    fputcsv($stream, $row, self::DELIMITER, '"', '');
  }
}
