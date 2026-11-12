<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Validator;

use EcomailFlexibee\Exception\EcomailFlexibeeInvalidRequestParameter;

use function mb_strlen;
use function mb_strtoupper;
use function str_contains;

final class ParameterValidator
{

    private const CODE_MAX_LENGTH = 20;

    /**
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidRequestParameter
     */
    public function validateFlexibeeRequestCodeParameter(string $code): void
    {
        if (mb_strlen($code) <= self::CODE_MAX_LENGTH && mb_strtoupper($code) === $code && !str_contains($code, "'")) {
            return;
        }

        throw new EcomailFlexibeeInvalidRequestParameter(
            'Parameter code must have a maximum of 20 characters, must be an uppercase and must not contain an apostrophe',
        );
    }

}
