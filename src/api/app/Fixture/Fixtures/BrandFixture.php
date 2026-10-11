<?php

declare(strict_types=1);

namespace App\Fixture\Fixtures;

use App\Entity\Brand\Implementation\Brand;
use App\Fixture\FixtureData;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class BrandFixture implements FixtureInterface
{
  /**
   * @return string[]
   */
  public static function getBrandNames(): array
  {
    return FixtureData::get()['brands'];
  }

  public function load(ObjectManager $manager): void
  {
    foreach (self::getBrandNames() as $name) {
      $brand = new Brand();
      $brand->setName($name);

      $manager->persist($brand);
    }

    $manager->flush();
    $manager->clear();
  }
}
