<?php

declare(strict_types=1);

use App\Config;
use App\Error\JsonErrorHandler;
use App\Middleware\CorsMiddleware;
use App\Middleware\NotFoundExceptionMiddleware;
use App\Middleware\ValidationExceptionMiddleware;
use Slim\App;

return function (App $app) {
  $container = $app->getContainer();
  $config = $container->get(Config::class);

  $app->add(ValidationExceptionMiddleware::class);
  $app->add(NotFoundExceptionMiddleware::class);

  $app->addBodyParsingMiddleware();

  $errorMiddleware = $app->addErrorMiddleware(
    (bool) $config->get('display_error_details'),
    (bool) $config->get('log_errors'),
    (bool) $config->get('log_error_details')
  );

  // Always answer with JSON, even for 404/405 and unexpected errors.
  $errorMiddleware->setDefaultErrorHandler(
    new JsonErrorHandler($app->getCallableResolver(), $app->getResponseFactory())
  );

  // Outermost: apply CORS headers to every response (including errors) and
  // answer preflight requests before routing.
  $app->add(CorsMiddleware::class);
};
