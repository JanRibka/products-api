<?php

declare(strict_types=1);

namespace App\Entity\Product\Implementation;

use App\Entity\Brand\Contract\BrandInterface;
use App\Entity\Brand\Implementation\Brand;
use App\Entity\Material\Contract\MaterialInterface;
use App\Entity\Material\Implementation\Material;
use App\Entity\Product\Contract\ProductInterface;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: 'product')]
class Product implements ProductInterface
{
  #[Id]
  #[GeneratedValue(strategy: 'AUTO')]
  #[Column]
  /** @phpstan-ignore-next-line */
  private int $id;

  #[Column(unique: true, length: 20)]
  private string $code;

  #[Column(length: 255)]
  private string $name;

  #[Column(type: 'text', nullable: true)]
  private ?string $description = null;

  #[Column(type: 'decimal', precision: 10, scale: 2)]
  private string $price;

  #[ManyToOne(targetEntity: Brand::class)]
  #[JoinColumn(name: 'brandId', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
  private BrandInterface $brand;

  #[ManyToOne(targetEntity: Material::class)]
  #[JoinColumn(name: 'materialId', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
  private MaterialInterface $material;

  #[Column(type: 'datetime_immutable')]
  private \DateTimeImmutable $createdAt;

  #[Column(type: 'datetime_immutable')]
  private \DateTimeImmutable $updatedAt;

  // Getters
  public function getId(): int
  {
    return $this->id;
  }

  public function getCode(): string
  {
    return $this->code;
  }

  public function getName(): string
  {
    return $this->name;
  }

  public function getDescription(): ?string
  {
    return $this->description;
  }

  public function getPrice(): string
  {
    return $this->price;
  }

  public function getBrand(): BrandInterface
  {
    return $this->brand;
  }

  public function getMaterial(): MaterialInterface
  {
    return $this->material;
  }

  public function getCreatedAt(): \DateTimeImmutable
  {
    return $this->createdAt;
  }

  public function getUpdatedAt(): \DateTimeImmutable
  {
    return $this->updatedAt;
  }

  // Setters
  public function setCode(string $code): self
  {
    $this->code = $code;

    return $this;
  }

  public function setName(string $name): self
  {
    $this->name = $name;

    return $this;
  }

  public function setDescription(?string $description): self
  {
    $this->description = $description;

    return $this;
  }

  public function setPrice(string $price): self
  {
    $this->price = $price;

    return $this;
  }

  public function setBrand(BrandInterface $brand): self
  {
    $this->brand = $brand;

    return $this;
  }

  public function setMaterial(MaterialInterface $material): self
  {
    $this->material = $material;

    return $this;
  }

  public function setCreatedAt(): self
  {
    $this->createdAt = new \DateTimeImmutable();

    return $this;
  }

  public function setUpdatedAt(): self
  {
    $this->updatedAt = new \DateTimeImmutable();

    return $this;
  }
}
