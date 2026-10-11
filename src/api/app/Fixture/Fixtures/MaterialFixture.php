<?php

declare(strict_types=1);

namespace App\Fixture\Fixtures;

use App\Entity\Material\Implementation\Material;
use App\Fixture\FixtureData;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MaterialFixture implements FixtureInterface
{
  /**
   * @return string[]
   */
  public static function getMaterialNames(): array
  {
    return FixtureData::get()['materials'];
  }

  public function load(ObjectManager $manager): void
  {
    foreach (self::getMaterialNames() as $name) {
      $material = new Material();
      $material->setName($name);

      $manager->persist($material);
    }

    $manager->flush();
    $manager->clear();
  }
}
