<?php

declare(strict_types=1);

namespace Test\Integration\Http;

use Test\Integration\IntegrationTestCase;

/**
 * B1/Major: PUT /api/products/{product} validation rules.
 */
class ProductUpdateValidationTest extends IntegrationTestCase
{
  private function validPayload(array $overrides = []): array
  {
    return array_merge([
      'code' => 'PM00001',
      'name' => 'Valid name',
      'description' => 'Valid description',
      'price' => '99.99',
      'brandId' => $this->brandIds['Audi'],
      'materialId' => $this->materialIds['Plast'],
    ], $overrides);
  }

  public function testValidPayloadIsAccepted(): void
  {
    $response = $this->put('/api/products/1', $this->validPayload());

    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
  }

  /**
   * @return array<string, array{array<string, mixed>}>
   */
  public static function invalidPayloads(): array
  {
    return [
      'missing code' => [['code' => null]],
      'missing name' => [['name' => null]],
      'missing price' => [['price' => null]],
      'missing brandId' => [['brandId' => null]],
      'missing materialId' => [['materialId' => null]],
      'code too long' => [['code' => str_repeat('A', 21)]],
      'name too long' => [['name' => str_repeat('A', 256)]],
      'description too long' => [['description' => str_repeat('A', 5001)]],
      'price not numeric' => [['price' => 'abc']],
      'price negative' => [['price' => '-5']],
      'price zero' => [['price' => '0']],
      'price too many decimals' => [['price' => '10.999']],
      'brand does not exist' => [['brandId' => 999999]],
      'material does not exist' => [['materialId' => 999999]],
      'brandId wrong type array' => [['brandId' => [1, 2]]],
      'materialId wrong type array' => [['materialId' => [1, 2]]],
      'brandId wrong type object' => [['brandId' => ['x' => 1]]],
      'price wrong type array' => [['price' => [1, 2]]],
      'code wrong type array' => [['code' => ['a']]],
      'name wrong type array' => [['name' => ['a']]],
      'description wrong type array' => [['description' => ['a']]],
      'duplicate code of another product' => [['code' => 'PM00433']],
    ];
  }

  #[\PHPUnit\Framework\Attributes\DataProvider('invalidPayloads')]
  public function testInvalidPayloadIsRejectedWith422(array $overrides): void
  {
    $response = $this->put('/api/products/1', $this->validPayload($overrides));

    $this->assertSame(422, $response->getStatusCode(), (string) $response->getBody());
    $this->assertJsonContentType($response);

    $body = $this->json($response);
    $this->assertArrayHasKey('validationError', $body);
  }

  public function testValidationErrorsAreDeduplicated(): void
  {
    $response = $this->put('/api/products/1', $this->validPayload(['price' => '-5']));

    $this->assertSame(422, $response->getStatusCode());
    $body = $this->json($response);

    $errors = $body['validationError'][0]['price'] ?? [];
    $this->assertSame(
      array_values(array_unique($errors)),
      array_values($errors),
      'Price errors must not be duplicated: ' . json_encode($errors)
    );
  }

  public function testWrongTypeReportsFieldError(): void
  {
    $response = $this->put('/api/products/1', $this->validPayload(['brandId' => [1, 2]]));

    $this->assertSame(422, $response->getStatusCode());
    $body = $this->json($response);
    $this->assertArrayHasKey('brandId', $body['validationError'][0]);
  }
}
