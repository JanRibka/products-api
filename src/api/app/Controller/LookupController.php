<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Brand\Contract\BrandInterface;
use App\Entity\Material\Contract\MaterialInterface;
use App\Repository\Contract\BrandRepositoryInterface;
use App\Repository\Contract\MaterialRepositoryInterface;
use App\Shared\ResponseFormatter\ResponseFormatter;
use Psr\Http\Message\ResponseInterface as Response;

class LookupController
{
  public function __construct(
    private readonly BrandRepositoryInterface $brandRepository,
    private readonly MaterialRepositoryInterface $materialRepository,
    private readonly ResponseFormatter $responseFormatter
  ) {
  }

  public function brands(Response $response): Response
  {
    $brands = array_map(
      fn(BrandInterface $brand) => [
        'id' => $brand->getId(),
        'name' => $brand->getName(),
      ],
      $this->brandRepository->getAll()
    );

    return $this->responseFormatter->asJson($response, $brands);
  }

  public function materials(Response $response): Response
  {
    $materials = array_map(
      fn(MaterialInterface $material) => [
        'id' => $material->getId(),
        'name' => $material->getName(),
      ],
      $this->materialRepository->getAll()
    );

    return $this->responseFormatter->asJson($response, $materials);
  }
}
