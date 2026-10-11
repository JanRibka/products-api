<?php

declare(strict_types=1);

namespace App\Service\Implementation;

use App\Service\Contract\EntityManagerServiceInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @mixin EntityManagerInterface
 */
class EntityManagerService implements EntityManagerServiceInterface
{
  public function __construct(
    protected readonly EntityManagerInterface $entityManagerService
  ) {
  }

  public function __call(string $name, array $arguments)
  {
    if (method_exists($this->entityManagerService, $name)) {
      return call_user_func_array([$this->entityManagerService, $name], $arguments);
    }

    throw new \BadMethodCallException('Call to undefined method "' . $name . '"');
  }

  public function sync($entity = null): int|string|null
  {
    if ($entity) {
      $this->entityManagerService->persist($entity);
    }

    $this->entityManagerService->flush();

    if ($entity === null) {
      return null;
    }

    $classMetadata = $this->entityManagerService->getClassMetadata(get_class($entity));
    $identifier = $classMetadata->getIdentifierValues($entity);

    if (count($identifier) === 1) {
      return reset($identifier);
    }

    return null;
  }

  public function delete($entity, bool $sync = false): void
  {
    $this->entityManagerService->remove($entity);

    if ($sync) {
      $this->sync();
    }
  }

  public function clear(?string $entityName = null): void
  {
    if ($entityName === null) {
      $this->entityManagerService->clear();

      return;
    }

    $unitOfWork = $this->entityManagerService->getUnitOfWork();
    $entities = $unitOfWork->getIdentityMap()[$entityName] ?? [];

    foreach ($entities as $entity) {
      $this->entityManagerService->detach($entity);
    }
  }

  public function wrapInTransaction(callable $callback): mixed
  {
    return $this->entityManagerService->wrapInTransaction($callback);
  }
}
