<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Exception;

use Exception;
use Rakit\Validation\Validation;
use Throwable;

use function array_filter;
use function implode;
use function is_string;

use const PHP_EOL;

final class EcomailFlexibeeInvalidRequestParameter extends Exception
{

    public function __construct(Validation $validation, ?Throwable $previous = null)
    {
        parent::__construct(implode(PHP_EOL, array_filter($validation->errors()->all(), is_string(...))), 0, $previous);
    }

}
