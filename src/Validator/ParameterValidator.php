<?php declare(strict_types = 1);

namespace EcomailFlexibee\Validator;

use EcomailFlexibee\Exception\EcomailFlexibeeInvalidRequestParameter;
use Rakit\Validation\Validator;

final class ParameterValidator extends Validator
{

    public function validateFlexibeeRequestCodeParameter(string $code): void
    {
        $validation = $this->make(
            [
                'code' => $code,
            ],
            [
                'code' => 'max:20|uppercase',
            ],
            [
                'code' => 'Parameter code must have a maximum of 20 characters and must be an uppercase',
            ],
        );

        $validation->validate();

        if ($validation->fails()) {
            throw new EcomailFlexibeeInvalidRequestParameter($validation);
        }
    }

}
