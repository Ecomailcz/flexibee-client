<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Exception;

use Exception;
use Rakit\Validation\Validation;
use Throwable;
use function implode;
use const PHP_EOL;

final class EcomailFlexibeeInvalidRequestParameter extends Exception
{

    public function __construct(Validation $validation, ?Throwable $previous = null)
    {
        parent::__construct(implode(PHP_EOL, $validation->errors()->all()), 0, $previous);
    }

}
