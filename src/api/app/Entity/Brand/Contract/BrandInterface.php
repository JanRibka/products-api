<?php

declare(strict_types=1);

namespace App\Entity\Brand\Contract;

interface BrandInterface
{
  public function getId(): int;

  public function getName(): string;
}
