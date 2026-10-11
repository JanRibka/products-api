<?php

declare(strict_types=1);

const LOWERCASE_REGEX = '[a-z]';
const UPPERCASE_REGEX = '[A-Z]';
const LOWER_UPPERCASE_REGEX = '[a-zA-Z]';
const NUMBERS_REGEX = '[0-9]';
const EMAIL_END_REGEX = '\\.[a-zA-Z]{2,4}$';

function LowerUpperCaseNumberSpecialCharRegex(string $specialChar): string
{
  return '[a-zA-Z0-9' . $specialChar . ']';
}
