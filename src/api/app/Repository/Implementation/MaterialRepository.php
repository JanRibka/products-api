<?php

declare(strict_types=1);

namespace App\Repository\Implementation;

use App\Entity\Material\Contract\MaterialInterface;
use App\Entity\Material\Implementation\Material;
use App\Repository\Contract\MaterialRepositoryInterface;
use App\Service\Contract\EntityManagerServiceInterface;

class MaterialRepository implements MaterialRepositoryInterface
{
  public function __construct(
    private readonly EntityManagerServiceInterface $entityManagerService
  ) {
  }

  /**
   * @return MaterialInterface[]
   */
  public function getAll(): array
  {
    return $this->entityManagerService->getRepository(Material::class)
      ->createQueryBuilder('m')
      ->orderBy('m.name', 'ASC')
      ->getQuery()
      ->getResult();
  }

  public function getById(int $id): ?MaterialInterface
  {
    return $this->entityManagerService->getRepository(Material::class)->find($id);
  }
}
