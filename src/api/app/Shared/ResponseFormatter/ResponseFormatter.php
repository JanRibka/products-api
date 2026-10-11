<?php

declare(strict_types=1);

namespace App\Shared\ResponseFormatter;

use Psr\Http\Message\ResponseInterface;

class ResponseFormatter
{
  public function asJson(
    ResponseInterface $response,
    mixed $data,
    int $flag = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_THROW_ON_ERROR
  ): ResponseInterface {
    $response = $response->withHeader('Content-Type', 'application/json');

    $response->getBody()->write(json_encode($data, $flag));

    return $response;
  }

  public function asPaginated(
    ResponseInterface $response,
    array $data,
    int $page,
    int $perPage,
    int $total
  ): ResponseInterface {
    $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 0;

    return $this->asJson(
      $response,
      [
        'data' => $data,
        'meta' => [
          'page' => $page,
          'perPage' => $perPage,
          'total' => $total,
          'totalPages' => $totalPages,
        ],
      ]
    );
  }
}
