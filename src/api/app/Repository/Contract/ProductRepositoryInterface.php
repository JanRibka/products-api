<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\DataObject\Data\ProductListQuery;
use App\Entity\Product\Contract\ProductInterface;

interface ProductRepositoryInterface
{
  /**
   * @return ProductInterface[]
   */
  public function getList(ProductListQuery $query): array;

  public function countList(ProductListQuery $query): int;

  /**
   * @return ProductInterface[]
   */
  public function getForExport(ProductListQuery $query): array;

  public function getById(int $id): ?ProductInterface;

  public function getByCode(string $code): ?ProductInterface;

  public function update(ProductInterface $product): void;
}
