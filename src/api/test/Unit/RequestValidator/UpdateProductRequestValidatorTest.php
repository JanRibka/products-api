<?php

declare(strict_types=1);

namespace Test\Unit\RequestValidator;

use App\Exception\ValidationException;
use App\RequestValidator\Product\UpdateProductRequestValidator;
use App\Service\Contract\EntityManagerServiceInterface;
use PHPUnit\Framework\TestCase;

/**
 * README promised a dedicated Unit test for the PUT product validator,
 * covering every rule (including wrong input types) without a database.
 */
class UpdateProductRequestValidatorTest extends TestCase
{
  /**
   * Build a validator whose EntityManagerService double answers repository
   * calls: brands/materials existence and unique-code lookups.
   *
   * @param array{
   *   existingBrandIds?: int[],
   *   existingMaterialIds?: int[],
   *   codeOwnersByCode?: array<string, object|null>
   * } $behaviour
   */
  private function validator(array $behaviour = [], ?object $context = null): array
  {
    $existingBrandIds = $behaviour['existingBrandIds'] ?? [1];
    $existingMaterialIds = $behaviour['existingMaterialIds'] ?? [1];
    $codeOwners = $behaviour['codeOwnersByCode'] ?? [];

    $entityManagerService = $this->createStub(EntityManagerServiceInterface::class);
    $entityManagerService->method('__call')->willReturnCallback(
      function (string $name, array $arguments) use ($existingBrandIds, $existingMaterialIds, $codeOwners) {
        [$class] = $arguments;
        $repository = $this->createStub(\Doctrine\ORM\EntityRepository::class);

        if ($class === \App\Entity\Brand\Implementation\Brand::class) {
          $repository->method('count')->willReturnCallback(
            fn(array $criteria) => in_array($criteria['id'] ?? null, $existingBrandIds, true) ? 1 : 0
          );
        } elseif ($class === \App\Entity\Material\Implementation\Material::class) {
          $repository->method('count')->willReturnCallback(
            fn(array $criteria) => in_array($criteria['id'] ?? null, $existingMaterialIds, true) ? 1 : 0
          );
        } elseif ($class === \App\Entity\Product\Implementation\Product::class) {
          $repository->method('findOneBy')->willReturnCallback(
            fn(array $criteria) => $codeOwners[$criteria['code'] ?? ''] ?? null
          );
        }

        return $repository;
      }
    );

    return [new UpdateProductRequestValidator($entityManagerService), $entityManagerService];
  }

  private function assertValidationFails(array $data, string $field, ?object $context = null): array
  {
    [$validator] = $this->validator([], $context);

    try {
      $validator->validate($data, $context);
    } catch (ValidationException $exception) {
      $errors = $exception->errors['validationError'][0] ?? [];
      $this->assertArrayHasKey($field, $errors, "Expected error on $field, got " . json_encode($errors));

      return $errors;
    }

    $this->fail("Expected validation to fail on $field, but it passed");
  }

  private function validData(array $overrides = []): array
  {
    return array_merge([
      'code' => 'PM00001',
      'name' => 'Kryt',
      'description' => 'Popis',
      'price' => '100.00',
      'brandId' => 1,
      'materialId' => 1,
    ], $overrides);
  }

  public function testValidDataIsNormalizedAndReturned(): void
  {
    [$validator] = $this->validator();
    $result = $validator->validate($this->validData(['price' => '99.9']));

    $this->assertSame(1, $result['brandId']);
    $this->assertSame(1, $result['materialId']);
    $this->assertSame('99.90', $result['price']);
  }

  public static function requiredFields(): array
  {
    return [
      'code' => ['code'],
      'name' => ['name'],
      'price' => ['price'],
      'brandId' => ['brandId'],
      'materialId' => ['materialId'],
    ];
  }

  #[\PHPUnit\Framework\Attributes\DataProvider('requiredFields')]
  public function testRequiredRules(string $field): void
  {
    $errors = $this->assertValidationFails($this->validData([$field => null]), $field);
    $this->assertContains('required', $errors[$field]);
  }

  public function testCodeTooLong(): void
  {
    $errors = $this->assertValidationFails($this->validData(['code' => str_repeat('A', 21)]), 'code');
    $this->assertContains('code.tooLong', $errors['code']);
  }

  public function testNameTooLong(): void
  {
    $errors = $this->assertValidationFails($this->validData(['name' => str_repeat('A', 256)]), 'name');
    $this->assertContains('name.tooLong', $errors['name']);
  }

  public function testDescriptionTooLong(): void
  {
    $errors = $this->assertValidationFails(
      $this->validData(['description' => str_repeat('A', 5001)]),
      'description'
    );
    $this->assertContains('description.tooLong', $errors['description']);
  }

  public function testDescriptionIsOptional(): void
  {
    [$validator] = $this->validator();
    $result = $validator->validate($this->validData(['description' => null]));

    $this->assertNull($result['description']);
  }

  public function testPriceNotNumeric(): void
  {
    $errors = $this->assertValidationFails($this->validData(['price' => 'abc']), 'price');
    $this->assertContains('price.invalid', $errors['price']);
  }

  public function testPriceNegative(): void
  {
    $errors = $this->assertValidationFails($this->validData(['price' => '-5']), 'price');
    $this->assertContains('price.invalid', $errors['price']);
  }

  public function testPriceZero(): void
  {
    $errors = $this->assertValidationFails($this->validData(['price' => '0']), 'price');
    $this->assertContains('price.invalid', $errors['price']);
  }

  public function testPriceTooManyDecimals(): void
  {
    $errors = $this->assertValidationFails($this->validData(['price' => '10.999']), 'price');
    $this->assertContains('price.invalid', $errors['price']);
  }

  public function testBrandNotFound(): void
  {
    [$validator] = $this->validator(['existingBrandIds' => [1]]);

    try {
      $validator->validate($this->validData(['brandId' => 999]));
      $this->fail('Expected brand.notFound');
    } catch (ValidationException $exception) {
      $this->assertContains('brand.notFound', $exception->errors['validationError'][0]['brandId']);
    }
  }

  public function testMaterialNotFound(): void
  {
    [$validator] = $this->validator(['existingMaterialIds' => [1]]);

    try {
      $validator->validate($this->validData(['materialId' => 999]));
      $this->fail('Expected material.notFound');
    } catch (ValidationException $exception) {
      $this->assertContains('material.notFound', $exception->errors['validationError'][0]['materialId']);
    }
  }

  public static function wrongTypeCases(): array
  {
    return [
      'brandId array' => ['brandId', [1, 2]],
      'brandId object' => ['brandId', ['x' => 1]],
      'materialId array' => ['materialId', [1, 2]],
      'code array' => ['code', ['a']],
      'name array' => ['name', ['a']],
      'description array' => ['description', ['a']],
      'price array' => ['price', [1, 2]],
    ];
  }

  #[\PHPUnit\Framework\Attributes\DataProvider('wrongTypeCases')]
  public function testWrongTypesAreRejected(string $field, mixed $value): void
  {
    $errors = $this->assertValidationFails($this->validData([$field => $value]), $field);

    $this->assertTrue(
      in_array($field . '.invalidType', $errors[$field], true) || in_array('price.invalid', $errors[$field], true),
      "Expected a type error on $field, got " . json_encode($errors[$field])
    );
  }

  public function testCodeMustBeUniqueForAnotherProduct(): void
  {
    $owner = new \App\Entity\Product\Implementation\Product();
    $reflection = new \ReflectionProperty(\App\Entity\Product\Implementation\Product::class, 'id');
    $reflection->setValue($owner, 2);

    [$validator] = $this->validator(['codeOwnersByCode' => ['PM00001' => $owner]]);

    try {
      $validator->validate($this->validData(['code' => 'PM00001']));
      $this->fail('Expected code.unique');
    } catch (ValidationException $exception) {
      $this->assertContains('code.unique', $exception->errors['validationError'][0]['code']);
    }
  }

  public function testCodeUniqueRuleIgnoresTheEditedProductItself(): void
  {
    $context = new \App\Entity\Product\Implementation\Product();
    $reflection = new \ReflectionProperty(\App\Entity\Product\Implementation\Product::class, 'id');
    $reflection->setValue($context, 5);

    [$validator] = $this->validator(['codeOwnersByCode' => ['PM00001' => $context]]);

    // Same product editing its own code -> must pass.
    $result = $validator->validate($this->validData(['code' => 'PM00001']), $context);
    $this->assertSame('PM00001', $result['code']);
  }

  public function testDuplicateErrorMessagesAreDeduplicated(): void
  {
    $errors = $this->assertValidationFails($this->validData(['price' => '-5']), 'price');

    $this->assertSame(array_values(array_unique($errors['price'])), array_values($errors['price']));
  }
}
