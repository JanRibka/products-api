<?php

declare(strict_types=1);

namespace App\Shared;

use App\Exception\NotFoundException;
use App\Service\Contract\EntityManagerServiceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use Slim\Interfaces\InvocationStrategyInterface;

class RouteEntityBindingStrategy implements InvocationStrategyInterface
{
  public function __construct(
    private readonly EntityManagerServiceInterface $entityManagerService
  ) {
  }

  public function __invoke(
    callable $callable,
    ServerRequestInterface $request,
    ResponseInterface $response,
    array $routeArguments
  ): ResponseInterface {
    $callableReflection = $this->createReflectionForCallable($callable);
    $resolvedArguments = [];

    foreach ($callableReflection->getParameters() as $parameter) {
      $type = $parameter->getType();

      if (!$type) {
        continue;
      }

      if (!$type instanceof \ReflectionNamedType) {
        continue;
      }

      $paramName = $parameter->getName();
      $typeName = $type->getName();

      if ($type->isBuiltin()) {
        if ($typeName === 'array' && $paramName === 'args') {
          $resolvedArguments[] = $routeArguments;
        }

        continue;
      }

      if ($typeName === ServerRequestInterface::class) {
        $resolvedArguments[] = $request;
      } elseif ($typeName === ResponseInterface::class) {
        $resolvedArguments[] = $response;
      } else {
        $entityId = $routeArguments[$paramName] ?? null;

        // A missing or non-numeric identifier is a client error, not a
        // server error: resolve it to a 404 instead of leaking an
        // InvalidArgumentException (500) from the invocation layer.
        if ($entityId === null || !is_scalar($entityId) || !preg_match('/^\d+$/', (string) $entityId)) {
          throw new NotFoundException('Resource not found');
        }

        $entity = $this->entityManagerService->find($typeName, (int) $entityId);

        if (!$entity) {
          throw new NotFoundException('Resource not found');
        }

        $resolvedArguments[] = $entity;
      }
    }

    return $callable(...$resolvedArguments);
  }

  public function createReflectionForCallable(callable $callable): ReflectionFunctionAbstract
  {
    return is_array($callable)
      ? new ReflectionMethod($callable[0], $callable[1])
      : new ReflectionFunction($callable);
  }
}
