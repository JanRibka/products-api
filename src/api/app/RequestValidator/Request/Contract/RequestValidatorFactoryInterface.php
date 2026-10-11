<?php

declare(strict_types=1);

namespace App\RequestValidator\Request\Contract;

interface RequestValidatorFactoryInterface
{
  public function make(string $class): RequestValidatorInterface;
}
