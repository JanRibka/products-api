<?php

declare(strict_types=1);

use App\Controller\ProductController;
use Slim\Routing\RouteCollectorProxy;

function getProductRoutes(RouteCollectorProxy $api)
{
  $api->group('/products', function (RouteCollectorProxy $products) {
    $products->get('', [ProductController::class, 'list'])
      ->setName('products_list');
    $products->get('/export', [ProductController::class, 'export'])
      ->setName('products_export');
    $products->get('/{product}', [ProductController::class, 'detail'])
      ->setName('products_detail');
    $products->put('/{product}', [ProductController::class, 'update'])
      ->setName('products_update');
  });

  return $api;
}
