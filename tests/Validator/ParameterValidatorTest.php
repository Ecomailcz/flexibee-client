<?php

declare(strict_types = 1);

namespace EcomailFlexibeeTest\Validator;

use EcomailFlexibee\Exception\EcomailFlexibeeInvalidRequestParameter;
use EcomailFlexibee\Validator\ParameterValidator;
use PHPUnit\Framework\TestCase;

final class ParameterValidatorTest extends TestCase
{

    private ParameterValidator $validator;

    public function setUp(): void
    {
        parent::setUp();

        $this->validator = new ParameterValidator();
    }

    /**
     * @dataProvider getDataForTestValidateFlexibeeRequestCodeParameter
     * @throws       \EcomailFlexibee\Exception\EcomailFlexibeeInvalidRequestParameter
     */
    public function testValidateFlexibeeRequestCodeParameter(string $code, ?string $exceptionClass): void
    {
        if ($exceptionClass !== null) {
            $this->expectException($exceptionClass);
        }

        $this->validator->validateFlexibeeRequestCodeParameter($code);

    }

    /**
     * @return array<array<mixed>>
     */
    public function getDataForTestValidateFlexibeeRequestCodeParameter(): array
    {
        return [
            [
                'TEST',
                null,
            ],
            [
                'ahoj',
                EcomailFlexibeeInvalidRequestParameter::class,
            ],
            [
                'ASASASASASASASASASASASASASASAS',
                EcomailFlexibeeInvalidRequestParameter::class,
            ],
        ];
    }

}
