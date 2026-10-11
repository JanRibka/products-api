<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;
use Throwable;

class NotFoundException extends RuntimeException
{
  public function __construct(
    string $message = "Zdroj nenalezen",
    int $code = 404,
    ?Throwable $previous = null
  ) {
    parent::__construct($message, $code, $previous);
  }
}
