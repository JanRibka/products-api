<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Enum\HttpStatusCode;
use App\Exception\NotFoundException;
use App\Shared\ResponseFormatter\ResponseFormatter;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class NotFoundExceptionMiddleware implements MiddlewareInterface
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
    } catch (NotFoundException $ex) {
      $response = $this->responseFactory->createResponse(HttpStatusCode::NOT_FOUND->value);

      return $this->responseFormatter->asJson($response, [
        'error' => 'notFound',
        'message' => $ex->getMessage(),
      ]);
    }
  }
}
