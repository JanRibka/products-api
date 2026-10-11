<?php

declare(strict_types=1);

namespace App\Repository\Implementation;

use App\Entity\Brand\Contract\BrandInterface;
use App\Entity\Brand\Implementation\Brand;
use App\Repository\Contract\BrandRepositoryInterface;
use App\Service\Contract\EntityManagerServiceInterface;

class BrandRepository implements BrandRepositoryInterface
{
  public function __construct(
    private readonly EntityManagerServiceInterface $entityManagerService
  ) {
  }

  /**
   * @return BrandInterface[]
   */
  public function getAll(): array
  {
    return $this->entityManagerService->getRepository(Brand::class)
      ->createQueryBuilder('b')
      ->orderBy('b.name', 'ASC')
      ->getQuery()
      ->getResult();
  }

  public function getById(int $id): ?BrandInterface
  {
    return $this->entityManagerService->getRepository(Brand::class)->find($id);
  }
}
