<?php

declare(strict_types=1);

namespace App\Entity\Brand\Implementation;

use App\Entity\Brand\Contract\BrandInterface;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'brand')]
class Brand implements BrandInterface
{
  #[Id]
  #[GeneratedValue(strategy: 'AUTO')]
  #[Column]
  /** @phpstan-ignore-next-line */
  private int $id;

  #[Column(unique: true, length: 100)]
  private string $name;

  // Getters
  public function getId(): int
  {
    return $this->id;
  }

  public function getName(): string
  {
    return $this->name;
  }

  // Setters
  public function setName(string $name): self
  {
    $this->name = $name;

    return $this;
  }
}
