<?php

declare(strict_types=1);

namespace App\DataObject\Data;

/**
 * Parses and sanitizes product list/export query parameters.
 *
 * All access to the raw query array is guarded so a missing or malformed
 * parameter never triggers an error - it always falls back to a default.
 */
class ProductListQuery
{
  public const PER_PAGE = 10;

  public const DEFAULT_SORT = 'code';
  public const DEFAULT_ORDER = 'asc';

  /**
   * Whitelist of sortable columns mapped to their query builder aliases.
   */
  private const SORT_MAP = [
    'code' => 'p.code',
    'name' => 'p.name',
    'price' => 'p.price',
    'brand' => 'b.name',
    'material' => 'm.name',
  ];

  public function __construct(
    public readonly int $page = 1,
    public readonly string $sort = self::DEFAULT_SORT,
    public readonly string $order = self::DEFAULT_ORDER,
    public readonly ?int $brandId = null,
    public readonly ?int $materialId = null,
    public readonly ?string $priceFrom = null,
    public readonly ?string $priceTo = null,
    public readonly string $search = ''
  ) {
  }

  public static function fromArray(array $params): self
  {
    $page = isset($params['page']) && is_numeric($params['page']) ? (int) $params['page'] : 1;
    if ($page < 1) {
      $page = 1;
    }

    $sort = isset($params['sort']) && is_string($params['sort']) ? $params['sort'] : self::DEFAULT_SORT;
    if (!array_key_exists($sort, self::SORT_MAP)) {
      $sort = self::DEFAULT_SORT;
    }

    $order = isset($params['order']) && is_string($params['order'])
      ? strtolower($params['order'])
      : self::DEFAULT_ORDER;
    if (!in_array($order, ['asc', 'desc'], true)) {
      $order = self::DEFAULT_ORDER;
    }

    $brandId = isset($params['brandId']) && is_numeric($params['brandId']) && (int) $params['brandId'] > 0
      ? (int) $params['brandId']
      : null;

    $materialId = isset($params['materialId']) && is_numeric($params['materialId']) && (int) $params['materialId'] > 0
      ? (int) $params['materialId']
      : null;

    $priceFrom = self::normalizePrice($params['priceFrom'] ?? null);
    $priceTo = self::normalizePrice($params['priceTo'] ?? null);

    $search = isset($params['search']) && is_string($params['search']) ? trim($params['search']) : '';

    return new self($page, $sort, $order, $brandId, $materialId, $priceFrom, $priceTo, $search);
  }

  public function getSortColumn(): string
  {
    return self::SORT_MAP[$this->sort] ?? self::SORT_MAP[self::DEFAULT_SORT];
  }

  public function getOrder(): string
  {
    return $this->order === 'desc' ? 'DESC' : 'ASC';
  }

  public function getOffset(): int
  {
    return ($this->page - 1) * self::PER_PAGE;
  }

  /**
   * @return array{brandId: int|null, materialId: int|null, priceFrom: string|null,
   *   priceTo: string|null, search: string}
   */
  public function getFilters(): array
  {
    return [
      'brandId' => $this->brandId,
      'materialId' => $this->materialId,
      'priceFrom' => $this->priceFrom,
      'priceTo' => $this->priceTo,
      'search' => $this->search,
    ];
  }

  private static function normalizePrice(mixed $value): ?string
  {
    if (!is_scalar($value)) {
      return null;
    }

    $value = trim((string) $value);

    if ($value === '' || !is_numeric($value)) {
      return null;
    }

    return $value;
  }
}
