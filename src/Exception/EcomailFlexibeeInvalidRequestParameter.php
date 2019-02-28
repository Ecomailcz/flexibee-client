<?php declare(strict_types = 1);

namespace EcomailFlexibee\Exception;

use Consistence\PhpException;
use Rakit\Validation\Validation;
use Throwable;

final class EcomailFlexibeeInvalidRequestParameter extends PhpException
{

    public function __construct(Validation $validation, ?Throwable $previous = null)
    {
        parent::__construct(implode(PHP_EOL, $validation->errors()->all()), $previous);
    }

}
