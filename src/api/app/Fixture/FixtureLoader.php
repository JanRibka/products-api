<?php

declare(strict_types=1);

namespace App\Fixture;

use App\Fixture\Fixtures\BrandFixture;
use App\Fixture\Fixtures\MaterialFixture;
use App\Fixture\Fixtures\ProductFixture;
use Doctrine\Common\DataFixtures\Loader;

$loader = new Loader();
$loader->addFixture(new BrandFixture());
$loader->addFixture(new MaterialFixture());
$loader->addFixture(new ProductFixture());

return $loader;
