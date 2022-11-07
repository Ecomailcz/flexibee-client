<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Http\Response;

use Consistence\Enum\Enum;

final class ResponseStatusCode extends Enum
{

    public const BAD_REQUEST = 400;
    public const UNAUTHORIZED = 401;
    public const FORBIDDEN = 403;
    public const NOT_FOUND = 404;
    public const METHOD_NOT_ALLOWED = 405;
    public const NOT_ACCEPTABLE = 406;
    public const INTERNAL_ERROR = 500;

}