<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;
use Throwable;

class ValidationException extends RuntimeException
{
  public function __construct(
    public readonly array $errors,
    int $code,
    string $message = "Chyba validace",
    ?Throwable $previous = null
  ) {
    parent::__construct($message, $code, $previous);
  }
}
