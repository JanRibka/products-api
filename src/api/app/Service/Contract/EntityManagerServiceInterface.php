<?php

declare(strict_types=1);

namespace App\Service\Contract;

use Doctrine\ORM\EntityManagerInterface;

/**
 * @mixin EntityManagerInterface
 */
interface EntityManagerServiceInterface
{
  public function __call(string $name, array $arguments);

  public function sync($entity = null): int|string|null;

  public function delete($entity, bool $sync = false): void;

  public function clear(?string $entityName = null): void;

  /**
   * @template T
   * @param callable(): T $callback
   * @return T
   */
  public function wrapInTransaction(callable $callback): mixed;
}
