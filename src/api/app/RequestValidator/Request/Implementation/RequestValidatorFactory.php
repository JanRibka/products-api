<?php

declare(strict_types=1);

namespace App\RequestValidator\Request\Implementation;

use App\RequestValidator\Request\Contract\RequestValidatorFactoryInterface;
use App\RequestValidator\Request\Contract\RequestValidatorInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

class RequestValidatorFactory implements RequestValidatorFactoryInterface
{
  public function __construct(
    private readonly ContainerInterface $container
  ) {
  }

  public function make(string $class): RequestValidatorInterface
  {
    $validator = $this->container->get($class);

    if ($validator instanceof RequestValidatorInterface) {
      return $validator;
    }

    throw new RuntimeException('Failed to instantiate the request validator class "' . $class . '"');
  }
}
