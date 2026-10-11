<?php

declare(strict_types=1);

use App\Command\FixtureLoaderCommand;
use App\Command\SeedSqlDumperCommand;

return [
    FixtureLoaderCommand::class,
    SeedSqlDumperCommand::class,
];
