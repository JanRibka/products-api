<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Entity\Material\Contract\MaterialInterface;

interface MaterialRepositoryInterface
{
  /**
   * @return MaterialInterface[]
   */
  public function getAll(): array;

  public function getById(int $id): ?MaterialInterface;
}
