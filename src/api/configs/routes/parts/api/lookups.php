<?php

declare(strict_types=1);

use App\Controller\LookupController;
use Slim\Routing\RouteCollectorProxy;

function getLookupRoutes(RouteCollectorProxy $api)
{
  $api->get('/brands', [LookupController::class, 'brands'])
    ->setName('brands_list');
  $api->get('/materials', [LookupController::class, 'materials'])
    ->setName('materials_list');

  return $api;
}
