<?php

declare(strict_types=1);

namespace App\RequestValidator\Request\Contract;

interface RequestValidatorInterface
{
  /**
   * Validates the given input data.
   *
   * An optional context (e.g. an entity being edited) may be supplied by
   * validators that need it - for example the "unique code" rule has to
   * ignore the product currently being updated.
   *
   * @param array $data
   * @param object|null $context
   * @return array
   */
  public function validate(array $data, ?object $context = null): array;
}
