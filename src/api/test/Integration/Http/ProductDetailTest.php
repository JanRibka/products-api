<?php

declare(strict_types=1);

namespace Test\Integration\Http;

use Test\Integration\IntegrationTestCase;

/**
 * B1: GET/PUT /api/products/{product} must no longer return 500.
 */
class ProductDetailTest extends IntegrationTestCase
{
  public function testGetExistingProductReturns200WithTheProduct(): void
  {
    $response = $this->get('/api/products/1');

    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    $this->assertJsonContentType($response);

    $body = $this->json($response);
    $this->assertSame(1, $body['id']);
    // The first seeded product (id 1) is the first row of the fixture data.
    $this->assertSame('PM00431', $body['code']);
    $this->assertArrayHasKey('brandName', $body);
    $this->assertArrayHasKey('materialName', $body);
  }

  public function testGetNonexistentProductReturnsJson404(): void
  {
    $response = $this->get('/api/products/999999');

    $this->assertSame(404, $response->getStatusCode(), (string) $response->getBody());
    $this->assertJsonContentType($response);

    $body = $this->json($response);
    $this->assertSame('notFound', $body['error']);
  }

  public function testGetNonNumericProductIdReturnsClean4xx(): void
  {
    $response = $this->get('/api/products/not-a-number');

    $this->assertSame(404, $response->getStatusCode(), (string) $response->getBody());
    $this->assertJsonContentType($response);

    $body = $this->json($response);
    $this->assertArrayHasKey('error', $body);
  }

  public function testPutHappyPathUpdatesProductAndUpdatedAt(): void
  {
    $before = $this->json($this->get('/api/products/1'));

    // Make sure the clock can move so updatedAt is observably different.
    usleep(1000000);

    $payload = [
      'code' => 'PM00001',
      'name' => 'Renamed cover',
      'description' => 'Updated description',
      'price' => '4321.00',
      'brandId' => $this->brandIds['BMW'],
      'materialId' => $this->materialIds['Plech'],
    ];

    $response = $this->put('/api/products/1', $payload);

    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    $this->assertJsonContentType($response);

    $updated = $this->json($response);
    $this->assertSame('Renamed cover', $updated['name']);
    $this->assertSame('4321.00', $updated['price']);
    $this->assertSame($this->brandIds['BMW'], $updated['brandId']);
    $this->assertSame($this->materialIds['Plech'], $updated['materialId']);
    $this->assertNotSame($before['updatedAt'], $updated['updatedAt']);

    // A following GET must reflect the persisted change.
    $after = $this->json($this->get('/api/products/1'));
    $this->assertSame('Renamed cover', $after['name']);
    $this->assertSame('4321.00', $after['price']);
    $this->assertSame($updated['updatedAt'], $after['updatedAt']);
  }

  public function testPutNonexistentProductReturnsJson404(): void
  {
    $response = $this->put('/api/products/999999', [
      'code' => 'PM99999',
      'name' => 'X',
      'description' => null,
      'price' => '10.00',
      'brandId' => $this->brandIds['Audi'],
      'materialId' => $this->materialIds['Plast'],
    ]);

    $this->assertSame(404, $response->getStatusCode(), (string) $response->getBody());
    $this->assertJsonContentType($response);
  }
}
