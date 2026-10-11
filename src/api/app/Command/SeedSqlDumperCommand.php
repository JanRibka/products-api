<?php

declare(strict_types=1);

namespace App\Command;

use App\Fixture\FixtureData;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Regenerates database/seed.sql from the single source of truth (FixtureData).
 *
 * The SQL snapshot in database/ mirrors the fixtures exactly, so it must be
 * regenerated whenever the fixture data changes. Keeping this as a command
 * means the snapshot can never drift silently from the data it mirrors.
 */
#[AsCommand(
  name: 'fixtures:dump-sql',
  description: 'Writes database/seed.sql from the fixture data (single source of truth)'
)]
class SeedSqlDumperCommand extends Command
{
  /** Path to database/seed.sql, resolved from the application root. */
  private const OUTPUT_PATH = __DIR__ . '/../../../../database/seed.sql';

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $data = FixtureData::get();

    $quote = static fn(string $value): string => "'" . str_replace("'", "''", $value) . "'";

    $brandIds = [];
    foreach ($data['brands'] as $index => $name) {
      $brandIds[$name] = $index + 1;
    }
    $materialIds = [];
    foreach ($data['materials'] as $index => $name) {
      $materialIds[$name] = $index + 1;
    }

    $brandRows = array_map(static fn(string $name): string => '  (' . $quote($name) . ')', $data['brands']);
    $materialRows = array_map(static fn(string $name): string => '  (' . $quote($name) . ')', $data['materials']);

    $productRows = [];
    foreach ($data['products'] as $product) {
      $productRows[] = '  (' . implode(', ', [
        $quote($product['code']),
        $quote($product['name']),
        $quote($product['description']),
        $product['price'],
        $brandIds[$product['brand']],
        $materialIds[$product['material']],
        'NOW()',
        'NOW()',
      ]) . ')';
    }

    $brandMap = [];
    foreach ($brandIds as $name => $id) {
      $brandMap[] = $id . ' ' . $name;
    }
    $materialMap = [];
    foreach ($materialIds as $name => $id) {
      $materialMap[] = $id . ' ' . $name;
    }

    $sql = "-- Products API - seed data (mirrors the application fixtures)\n"
      . "--\n"
      . "-- GENERATED FILE - do not edit by hand. Regenerate with:\n"
      . "--   php tracker fixtures:dump-sql\n"
      . "-- The data lives in src/api/app/Fixture/data/products.php (single source of truth).\n"
      . "SET NAMES utf8mb4;\n\n"
      . "TRUNCATE TABLE `product`;\n"
      . "DELETE FROM `brand`;\n"
      . "DELETE FROM `material`;\n"
      . "ALTER TABLE `brand` AUTO_INCREMENT = 1;\n"
      . "ALTER TABLE `material` AUTO_INCREMENT = 1;\n\n"
      . "INSERT INTO `brand` (`name`) VALUES\n" . implode(",\n", $brandRows) . ";\n\n"
      . "INSERT INTO `material` (`name`) VALUES\n" . implode(",\n", $materialRows) . ";\n\n"
      . '-- brandId:    ' . implode(', ', $brandMap) . "\n"
      . '-- materialId: ' . implode(', ', $materialMap) . "\n"
      . 'INSERT INTO `product` (`code`, `name`, `description`, `price`, `brandId`, `materialId`,'
      . " `createdAt`, `updatedAt`) VALUES\n"
      . implode(",\n", $productRows) . ";\n";

    if (file_put_contents(self::OUTPUT_PATH, $sql) === false) {
      $output->writeln('<error>Could not write ' . self::OUTPUT_PATH . '</error>');

      return Command::FAILURE;
    }

    $output->writeln(sprintf(
      '<info>Wrote %s</info> (%d products)',
      realpath(self::OUTPUT_PATH) ?: self::OUTPUT_PATH,
      count($data['products'])
    ));

    return Command::SUCCESS;
  }
}
