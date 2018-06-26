<?php declare(strict_types = 1);

namespace EcomailFlexibee\Exception;

use Consistence\PhpException;
use Throwable;

class EcomailFlexibeeInvalidAuthorization extends PhpException
{

    public function __construct(string $username, string $password, string $url, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Invalid authentication for %s:%s (%s)', $username, $password, $url), $previous);
    }

}
