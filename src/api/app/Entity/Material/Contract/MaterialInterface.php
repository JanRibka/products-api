<?php

declare(strict_types=1);

namespace App\Entity\Material\Contract;

interface MaterialInterface
{
  public function getId(): int;

  public function getName(): string;
}
