<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Entity\Brand\Contract\BrandInterface;

interface BrandRepositoryInterface
{
  /**
   * @return BrandInterface[]
   */
  public function getAll(): array;

  public function getById(int $id): ?BrandInterface;
}
