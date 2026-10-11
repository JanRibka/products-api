<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Enum\HttpStatusCode;
use App\Exception\ValidationException;
use App\Shared\ResponseFormatter\ResponseFormatter;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ValidationExceptionMiddleware implements MiddlewareInterface
{
  public function __construct(
    private readonly ResponseFactoryInterface $responseFactory,
    private readonly ResponseFormatter $responseFormatter
  ) {
  }

  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
    try {
      return $handler->handle($request);
    } catch (ValidationException $ex) {
      $response = $this->responseFactory->createResponse();

      return $this->responseFormatter->asJson(
        $response->withStatus($ex->getCode() ?: HttpStatusCode::UNPROCESSABLE_ENTITY->value),
        $ex->errors
      );
    }
  }
}
