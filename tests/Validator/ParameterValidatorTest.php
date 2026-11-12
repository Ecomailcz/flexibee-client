<?php

declare(strict_types = 1);

namespace EcomailFlexibeeTest\Validator;

use EcomailFlexibee\Exception\EcomailFlexibeeInvalidRequestParameter;
use EcomailFlexibee\Validator\ParameterValidator;
use Iterator;
use PHPUnit\Framework\TestCase;

final class ParameterValidatorTest extends TestCase
{

    private ParameterValidator $validator;

    /**
     * @dataProvider getDataForTestValidateFlexibeeRequestCodeParameter
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidRequestParameter
     * @param class-string<\Throwable>|null $exceptionClass
     */
    public function testValidateFlexibeeRequestCodeParameter(string $code, ?string $exceptionClass): void
    {
        if ($exceptionClass !== null) {
            $this->expectException($exceptionClass);
        }

        $this->validator->validateFlexibeeRequestCodeParameter($code);
    }

    /**
     * @return \Iterator<int, array{string, class-string<\Throwable>|null}>
     */
    public function getDataForTestValidateFlexibeeRequestCodeParameter(): Iterator
    {
        yield [
            'TEST',
            null,
        ];

        yield [
            'ahoj',
            EcomailFlexibeeInvalidRequestParameter::class,
        ];

        yield [
            'ASASASASASASASASASASASASASASAS',
            EcomailFlexibeeInvalidRequestParameter::class,
        ];
    }

    public function testReportsOneMessageWhenBothRulesFail(): void
    {
        $this->expectException(EcomailFlexibeeInvalidRequestParameter::class);
        $this->expectExceptionMessageMatches(
            '/^Parameter code must have a maximum of 20 characters, must be an uppercase and must not contain an apostrophe$/',
        );

        $this->validator->validateFlexibeeRequestCodeParameter('lowercase-code-longer-than-twenty');
    }

    public function testAcceptsUppercaseCodeWithDiacriticsAtTheLengthLimit(): void
    {
        $this->expectNotToPerformAssertions();

        $this->validator->validateFlexibeeRequestCodeParameter('ŽLUŤOUČKÝ-KŮŇ-ÚPĚL-Á');
    }

    public function testRejectsCodeWithApostrophe(): void
    {
        $this->expectException(EcomailFlexibeeInvalidRequestParameter::class);

        $this->validator->validateFlexibeeRequestCodeParameter("A') OR (1=1");
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new ParameterValidator();
    }

}
