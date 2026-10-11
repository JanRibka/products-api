<?php

declare(strict_types=1);

namespace App\Fixture\Fixtures;

use App\Entity\Brand\Implementation\Brand;
use App\Entity\Material\Implementation\Material;
use App\Entity\Product\Implementation\Product;
use App\Fixture\FixtureData;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProductFixture implements FixtureInterface, DependentFixtureInterface
{
  /**
   * Product rows for the fixtures (see App\Fixture\FixtureData for details).
   *
   * The data is kept in one place (a PHP array in FixtureData) so the loader
   * command, the tests and database/seed.sql all describe the same set. Note
   * that not every brand/material has to appear in the products: Hliník is a
   * valid material but intentionally has no products.
   *
   * @return array<int, array{code: string, name: string, description: string,
   *   price: string, brand: string, material: string}>
   */
  public static function getProducts(): array
  {
    return FixtureData::get()['products'];
  }

  /**
   * @return array<int, class-string>
   */
  public function getDependencies(): array
  {
    return [
      BrandFixture::class,
      MaterialFixture::class,
    ];
  }

  public function load(ObjectManager $manager): void
  {
    $brands = [];
    foreach ($manager->getRepository(Brand::class)->findAll() as $brand) {
      $brands[$brand->getName()] = $brand;
    }

    $materials = [];
    foreach ($manager->getRepository(Material::class)->findAll() as $material) {
      $materials[$material->getName()] = $material;
    }

    foreach (self::getProducts() as $row) {
      $product = new Product();
      $product
        ->setCode($row['code'])
        ->setName($row['name'])
        ->setDescription($row['description'])
        ->setPrice($row['price'])
        ->setBrand($brands[$row['brand']])
        ->setMaterial($materials[$row['material']])
        ->setCreatedAt()
        ->setUpdatedAt();

      $manager->persist($product);
    }

    $manager->flush();
    $manager->clear();
  }
}
