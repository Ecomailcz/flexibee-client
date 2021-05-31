<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Http;

use Consistence\Enum\Enum;

final class Method extends Enum
{

    public const GET = 'GET';
    public const POST = 'POST';
    public const DELETE = 'DELETE';
    public const PUT = 'PUT';

}