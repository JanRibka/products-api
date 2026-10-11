<?php

declare(strict_types=1);

namespace App\Entity\Product\Contract;

use App\Entity\Brand\Contract\BrandInterface;
use App\Entity\Material\Contract\MaterialInterface;

interface ProductInterface
{
  public function getId(): int;

  public function getCode(): string;

  public function getName(): string;

  public function getDescription(): ?string;

  public function getPrice(): string;

  public function getBrand(): BrandInterface;

  public function getMaterial(): MaterialInterface;

  public function getCreatedAt(): \DateTimeImmutable;

  public function getUpdatedAt(): \DateTimeImmutable;

  public function setCode(string $code): self;

  public function setName(string $name): self;

  public function setDescription(?string $description): self;

  public function setPrice(string $price): self;

  public function setBrand(BrandInterface $brand): self;

  public function setMaterial(MaterialInterface $material): self;

  public function setCreatedAt(): self;

  public function setUpdatedAt(): self;
}
