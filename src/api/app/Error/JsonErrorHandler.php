<?php

declare(strict_types=1);

namespace App\Error;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Handlers\ErrorHandler;
use Throwable;

/**
 * Renders every uncaught error as JSON.
 *
 * The default Slim handler negotiates the content type from the Accept header
 * and falls back to HTML, which leaks file paths and stack traces. This
 * handler always answers with the project's JSON error format and only exposes
 * details when display_error_details is enabled (development).
 *
 * Logging is inherited from Slim's ErrorHandler (the application's existing
 * logging mechanism) so errors keep being written to the error log.
 */
class JsonErrorHandler extends ErrorHandler
{
  protected function determineContentType(ServerRequestInterface $request): ?string
  {
    return 'application/json';
  }

  protected function respond(): ResponseInterface
  {
    $statusCode = $this->exception instanceof HttpException
      ? $this->exception->getCode()
      : 500;

    if ($statusCode < 400 || $statusCode > 599) {
      $statusCode = 500;
    }

    $response = $this->responseFactory->createResponse($statusCode)
      ->withHeader('Content-Type', 'application/json');

    if ($this->exception instanceof HttpMethodNotAllowedException) {
      $allowedMethods = implode(', ', $this->exception->getAllowedMethods());
      $response = $response->withHeader('Allow', $allowedMethods);
    }

    $payload = [
      'error' => $this->errorKey($this->exception),
      'message' => $this->publicMessage($this->exception, $statusCode),
    ];

    if ($this->displayErrorDetails) {
      $payload['debug'] = [
        'exception' => $this->exception::class,
        'message' => $this->exception->getMessage(),
        'file' => $this->exception->getFile(),
        'line' => $this->exception->getLine(),
        'trace' => $this->exception->getTraceAsString(),
      ];
    }

    $response->getBody()->write(json_encode($payload, JSON_THROW_ON_ERROR));

    return $response;
  }

  private function errorKey(Throwable $exception): string
  {
    if ($exception instanceof HttpMethodNotAllowedException) {
      return 'methodNotAllowed';
    }

    if ($exception instanceof HttpException && $exception->getCode() === 404) {
      return 'notFound';
    }

    if ($exception instanceof HttpException && $exception->getCode() === 405) {
      return 'methodNotAllowed';
    }

    if ($exception instanceof HttpException) {
      return 'httpError';
    }

    return 'serverError';
  }

  /**
   * In production the message must never leak internals (exception text,
   * SQL, file paths). Generic messages are used for unexpected errors.
   */
  private function publicMessage(Throwable $exception, int $statusCode): string
  {
    if ($this->displayErrorDetails) {
      return $exception->getMessage();
    }

    if ($exception instanceof HttpException) {
      return $exception->getMessage() !== '' ? $exception->getMessage() : 'HTTP error';
    }

    return 'Internal server error';
  }
}
