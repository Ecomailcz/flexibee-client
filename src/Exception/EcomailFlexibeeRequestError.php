<?php declare(strict_types = 1);

namespace EcomailFlexibee\Exception;

use Consistence\PhpException;
use Throwable;

class EcomailFlexibeeRequestError extends PhpException
{

    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }

}
