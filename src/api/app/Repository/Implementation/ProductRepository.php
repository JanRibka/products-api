<?php

declare(strict_types=1);

namespace App\Repository\Implementation;

use App\DataObject\Data\ProductListQuery;
use App\Entity\Product\Contract\ProductInterface;
use App\Entity\Product\Implementation\Product;
use App\Repository\Contract\ProductRepositoryInterface;
use App\Service\Contract\EntityManagerServiceInterface;
use Doctrine\ORM\QueryBuilder;

class ProductRepository implements ProductRepositoryInterface
{
  public function __construct(
    private readonly EntityManagerServiceInterface $entityManagerService
  ) {
  }

  /**
   * @return ProductInterface[]
   */
  public function getList(ProductListQuery $query): array
  {
    $qb = $this->buildQueryBuilder($query)
      ->setFirstResult($query->getOffset())
      ->setMaxResults(ProductListQuery::PER_PAGE);

    return $qb->getQuery()->getResult();
  }

  public function countList(ProductListQuery $query): int
  {
    $qb = $this->entityManagerService->createQueryBuilder()
      ->select('COUNT(p.id)')
      ->from(Product::class, 'p')
      ->innerJoin('p.brand', 'b')
      ->innerJoin('p.material', 'm');

    $this->applyFilters($qb, $query);

    return (int) $qb->getQuery()->getSingleScalarResult();
  }

  /**
   * @return ProductInterface[]
   */
  public function getForExport(ProductListQuery $query): array
  {
    return $this->buildQueryBuilder($query)->getQuery()->getResult();
  }

  public function getById(int $id): ?ProductInterface
  {
    return $this->entityManagerService->getRepository(Product::class)->find($id);
  }

  public function getByCode(string $code): ?ProductInterface
  {
    return $this->entityManagerService->getRepository(Product::class)
      ->findOneBy(['code' => $code]);
  }

  public function update(ProductInterface $product): void
  {
    $this->entityManagerService->wrapInTransaction(function () use ($product) {
      $this->entityManagerService->sync($product);
    });
  }

  /**
   * Shared query builder for both the paginated list and the CSV export.
   */
  private function buildQueryBuilder(ProductListQuery $query): QueryBuilder
  {
    $qb = $this->entityManagerService->createQueryBuilder()
      ->select('p', 'b', 'm')
      ->from(Product::class, 'p')
      ->innerJoin('p.brand', 'b')
      ->innerJoin('p.material', 'm');

    $this->applyFilters($qb, $query);

    // Primary sort by the requested column, with a deterministic tie-breaker
    // (p.id) so paginated pages and the CSV export share one stable total
    // order - otherwise equal keys (price/brand/material) could duplicate or
    // drop records across LIMIT/OFFSET queries.
    $qb->orderBy($query->getSortColumn(), $query->getOrder())
      ->addOrderBy('p.id', 'ASC');

    return $qb;
  }

  private function applyFilters(QueryBuilder $qb, ProductListQuery $query): void
  {
    $filters = $query->getFilters();

    if ($filters['brandId'] !== null) {
      $qb->andWhere('p.brand = :brandId')
        ->setParameter('brandId', $filters['brandId']);
    }

    if ($filters['materialId'] !== null) {
      $qb->andWhere('p.material = :materialId')
        ->setParameter('materialId', $filters['materialId']);
    }

    if ($filters['priceFrom'] !== null) {
      $qb->andWhere('p.price >= :priceFrom')
        ->setParameter('priceFrom', $filters['priceFrom']);
    }

    if ($filters['priceTo'] !== null) {
      $qb->andWhere('p.price <= :priceTo')
        ->setParameter('priceTo', $filters['priceTo']);
    }

    if ($filters['search'] !== '') {
      // Escape LIKE wildcards so a literal "%" or "_" in the term does not
      // match everything. The backslash is declared via the ESCAPE clause.
      $term = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['search']);

      $qb->andWhere('p.name LIKE :search ESCAPE \'\\\' OR p.code LIKE :search ESCAPE \'\\\'')
        ->setParameter('search', '%' . $term . '%');
    }
  }
}
