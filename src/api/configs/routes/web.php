<?php

declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
  $app->group('/api', function (RouteCollectorProxy $api) {
    getProductRoutes($api);
    getLookupRoutes($api);
  });
};
