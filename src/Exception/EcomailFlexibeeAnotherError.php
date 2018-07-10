<?php declare(strict_types = 1);

namespace EcomailFlexibee\Exception;

use Consistence\PhpException;
use Throwable;

class EcomailFlexibeeAnotherError extends PhpException
{

    public function __construct(array $responseData, ?Throwable $previous = null)
    {
        parent::__construct(json_encode($responseData, JSON_UNESCAPED_UNICODE), $previous);
    }

}
