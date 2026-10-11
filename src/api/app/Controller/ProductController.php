<?php

declare(strict_types=1);

namespace App\Controller;

use App\DataObject\Data\ProductListQuery;
use App\Entity\Product\Contract\ProductInterface;
use App\Entity\Product\Implementation\Product;
use App\Enum\HttpStatusCode;
use App\Repository\Contract\BrandRepositoryInterface;
use App\Repository\Contract\MaterialRepositoryInterface;
use App\Repository\Contract\ProductRepositoryInterface;
use App\RequestValidator\Product\UpdateProductRequestValidator;
use App\RequestValidator\Request\Contract\RequestValidatorFactoryInterface;
use App\Shared\CsvExporter;
use App\Shared\ResponseFormatter\ResponseFormatter;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProductController
{
  public function __construct(
    private readonly RequestValidatorFactoryInterface $requestValidatorFactory,
    private readonly ProductRepositoryInterface $productRepository,
    private readonly BrandRepositoryInterface $brandRepository,
    private readonly MaterialRepositoryInterface $materialRepository,
    private readonly ResponseFormatter $responseFormatter,
    private readonly CsvExporter $csvExporter
  ) {
  }

  public function list(Request $request, Response $response): Response
  {
    $query = ProductListQuery::fromArray($request->getQueryParams());

    $products = $this->productRepository->getList($query);
    $total = $this->productRepository->countList($query);

    $data = array_map(fn(ProductInterface $product) => $this->serialize($product), $products);

    return $this->responseFormatter->asPaginated(
      $response,
      $data,
      $query->page,
      ProductListQuery::PER_PAGE,
      $total
    );
  }

  public function export(Request $request, Response $response): Response
  {
    $query = ProductListQuery::fromArray($request->getQueryParams());

    $products = $this->productRepository->getForExport($query);

    $stream = fopen('php://temp', 'r+');

    if ($stream === false) {
      throw new \RuntimeException('Unable to open export stream');
    }

    $this->csvExporter->writeBom($stream);
    $this->csvExporter->writeRow($stream, $this->csvExporter->getHeader());

    foreach ($products as $product) {
      $this->csvExporter->writeRow($stream, $this->csvExporter->productToRow($product));
    }

    rewind($stream);
    $content = (string) stream_get_contents($stream);
    fclose($stream);

    $response->getBody()->write($content);

    return $response
      ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
      ->withHeader('Content-Disposition', 'attachment; filename="products.csv"');
  }

  public function detail(Product $product, Response $response): Response
  {
    return $this->responseFormatter->asJson($response, $this->serialize($product));
  }

  public function update(Request $request, Response $response, Product $product): Response
  {
    $data = $this->requestValidatorFactory->make(UpdateProductRequestValidator::class)
      ->validate($request->getParsedBody() ?? [], $product);

    $brand = $this->brandRepository->getById($data['brandId']);
    $material = $this->materialRepository->getById($data['materialId']);

    // Validated above - both must exist.
    $product
      ->setCode($data['code'])
      ->setName($data['name'])
      ->setDescription($data['description'] ?? null)
      ->setPrice($data['price'])
      ->setBrand($brand)
      ->setMaterial($material)
      ->setUpdatedAt();

    $this->productRepository->update($product);

    return $this->responseFormatter->asJson(
      $response->withStatus(HttpStatusCode::OK->value),
      $this->serialize($product)
    );
  }

  /**
   * @return array<string, mixed>
   */
  private function serialize(ProductInterface $product): array
  {
    return [
      'id' => $product->getId(),
      'code' => $product->getCode(),
      'name' => $product->getName(),
      'description' => $product->getDescription(),
      'price' => $product->getPrice(),
      'brandId' => $product->getBrand()->getId(),
      'brandName' => $product->getBrand()->getName(),
      'materialId' => $product->getMaterial()->getId(),
      'materialName' => $product->getMaterial()->getName(),
      'createdAt' => $product->getCreatedAt()->format(\DateTimeInterface::ATOM),
      'updatedAt' => $product->getUpdatedAt()->format(\DateTimeInterface::ATOM),
    ];
  }
}
