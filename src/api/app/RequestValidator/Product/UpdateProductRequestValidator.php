<?php

declare(strict_types=1);

namespace App\RequestValidator\Product;

use App\Entity\Brand\Implementation\Brand;
use App\Entity\Material\Implementation\Material;
use App\Entity\Product\Contract\ProductInterface;
use App\Entity\Product\Implementation\Product;
use App\Enum\HttpStatusCode;
use App\Exception\ValidationException;
use App\RequestValidator\Request\Contract\RequestValidatorInterface;
use App\Service\Contract\EntityManagerServiceInterface;

class UpdateProductRequestValidator implements RequestValidatorInterface
{
  private const TEXT_MAX = [
    'code' => 20,
    'name' => 255,
    'description' => 5000,
  ];

  public function __construct(
    private readonly EntityManagerServiceInterface $entityManagerService
  ) {
  }

  /**
   * @param array $data
   * @param object|null $context The product being edited (for the unique-code exclusion)
   * @return array
   */
  public function validate(array $data, ?object $context = null): array
  {
    $product = $context instanceof ProductInterface ? $context : null;

    // Collected as field => list of messages; deduplicated at the end.
    $errors = [];

    $this->validateRequired($data, $errors);
    $this->validateTextFields($data, $errors);
    $this->validatePrice($data, $errors);
    $this->validateIds($data, $errors);
    $this->validateUniqueCode($data, $product, $errors);

    if ($errors !== []) {
      throw new ValidationException(
        ['validationError' => [$errors]],
        HttpStatusCode::UNPROCESSABLE_ENTITY->value
      );
    }

    // Normalize types for the controller.
    $data['brandId'] = (int) $data['brandId'];
    $data['materialId'] = (int) $data['materialId'];
    $data['price'] = number_format((float) $data['price'], 2, '.', '');
    $data['description'] = $data['description'] ?? null;

    return $data;
  }

  /**
   * @param array<string, mixed> $data
   * @param array<string, list<string>> $errors
   */
  private function validateRequired(array $data, array &$errors): void
  {
    foreach (['code', 'name', 'price', 'brandId', 'materialId'] as $field) {
      if (!array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
        $this->add($errors, $field, 'required');
      }
    }
  }

  /**
   * @param array<string, mixed> $data
   * @param array<string, list<string>> $errors
   */
  private function validateTextFields(array $data, array &$errors): void
  {
    foreach (self::TEXT_MAX as $field => $max) {
      if (!array_key_exists($field, $data) || $data[$field] === null) {
        continue;
      }

      if (!is_string($data[$field])) {
        $this->add($errors, $field, $field . '.invalidType');

        continue;
      }

      if ($data[$field] === '') {
        continue; // handled by validateRequired()
      }

      if (mb_strlen($data[$field]) > $max) {
        $this->add($errors, $field, $field . '.tooLong');
      }
    }
  }

  /**
   * @param array<string, mixed> $data
   * @param array<string, list<string>> $errors
   */
  private function validatePrice(array $data, array &$errors): void
  {
    if (!array_key_exists('price', $data) || $data['price'] === null || $data['price'] === '') {
      return; // handled by validateRequired()
    }

    if (!is_scalar($data['price']) || is_bool($data['price'])) {
      $this->add($errors, 'price', 'price.invalid');

      return;
    }

    $price = (string) $data['price'];

    if (!is_numeric($price) || (float) $price <= 0 || !preg_match('/^\d+(\.\d{1,2})?$/', $price)) {
      $this->add($errors, 'price', 'price.invalid');
    }
  }

  /**
   * @param array<string, mixed> $data
   * @param array<string, list<string>> $errors
   */
  private function validateIds(array $data, array &$errors): void
  {
    $targets = [
      'brandId' => Brand::class,
      'materialId' => Material::class,
    ];

    foreach ($targets as $field => $entityClass) {
      if (!array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
        continue; // handled by validateRequired()
      }

      if (!is_int($data[$field]) && !$this->isIntegerString($data[$field])) {
        $this->add($errors, $field, $field . '.invalidType');

        continue;
      }

      $id = (int) $data[$field];

      if ($id <= 0 || $this->entityManagerService->getRepository($entityClass)->count(['id' => $id]) === 0) {
        $this->add($errors, $field, $field === 'brandId' ? 'brand.notFound' : 'material.notFound');
      }
    }
  }

  private function isIntegerString(mixed $value): bool
  {
    return is_string($value) && preg_match('/^\d+$/', $value) === 1;
  }

  /**
   * @param array<string, mixed> $data
   * @param array<string, list<string>> $errors
   */
  private function validateUniqueCode(array $data, ?ProductInterface $product, array &$errors): void
  {
    if (!isset($data['code']) || !is_string($data['code']) || $data['code'] === '') {
      return;
    }

    $existing = $this->entityManagerService->getRepository(Product::class)
      ->findOneBy(['code' => $data['code']]);

    if ($existing !== null && ($product === null || $existing->getId() !== $product->getId())) {
      $this->add($errors, 'code', 'code.unique');
    }
  }

  /**
   * @param array<string, list<string>> $errors
   */
  private function add(array &$errors, string $field, string $message): void
  {
    if (!isset($errors[$field])) {
      $errors[$field] = [];
    }

    if (!in_array($message, $errors[$field], true)) {
      $errors[$field][] = $message;
    }
  }
}
