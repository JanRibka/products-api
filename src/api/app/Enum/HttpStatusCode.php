<?php

declare(strict_types=1);

namespace App\Enum;

enum HttpStatusCode: int
{
  /**
   * Informational 1xx
   */
  case CONTINUING = 100;
  case SWITCHING_PROTOCOLS = 101;
  case PROCESSING = 102;
  case EARLY_HINTS = 103;

  /**
   * Successful 2xx
   */
  case OK = 200;
  case CREATED = 201;
  case ACCEPTED = 202;
  case NON_AUTHORITATIVE_INFORMATION = 203;
  case NO_CONTENT = 204;
  case RESET_CONTENT = 205;
  case PARTIAL_CONTENT = 206;

  /**
   * Redirection 3xx
   */
  case MOVED_PERMANENTLY = 301;
  case FOUND = 302;
  case SEE_OTHER = 303;
  case NOT_MODIFIED = 304;
  case TEMPORARY_REDIRECT = 307;
  case PERMANENT_REDIRECT = 308;

  /**
   * Client Error 4xx
   */
  case BAD_REQUEST = 400;
  case UNAUTHORIZED = 401;
  case FORBIDDEN = 403;
  case NOT_FOUND = 404;
  case METHOD_NOT_ALLOWED = 405;
  case NOT_ACCEPTABLE = 406;
  case REQUEST_TIMEOUT = 408;
  case CONFLICT = 409;
  case GONE = 410;
  case PAYLOAD_TOO_LARGE = 413;
  case UNSUPPORTED_MEDIA_TYPE = 415;
  case UNPROCESSABLE_ENTITY = 422;
  case TOO_MANY_REQUESTS = 429;

  /**
   * Server Error 5xx
   */
  case INTERNAL_SERVER_ERROR = 500;
  case NOT_IMPLEMENTED = 501;
  case BAD_GATEWAY = 502;
  case SERVICE_UNAVAILABLE = 503;
  case GATEWAY_TIMEOUT = 504;
}
