<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Http;

enum Method: string
{

    case GET = 'GET';
    case POST = 'POST';
    case DELETE = 'DELETE';
    case PUT = 'PUT';

    public function __toString(): string
    {
    	/** @phpstan-ignore-next-line */
    	return $this->value;
    }

}
