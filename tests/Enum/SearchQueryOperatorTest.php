<?php

declare(strict_types = 1);

namespace EcomailFlexibeeTest\Enum;

use EcomailFlexibee\Enum\SearchQueryOperator;
use Iterator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class SearchQueryOperatorTest extends TestCase
{

    /**
     * @dataProvider getQueryStrings
     */
    public function testConvertOperators(string $query, string $expectedQuery): void
    {
        Assert::assertSame($expectedQuery, SearchQueryOperator::convertOperatorsInQuery($query));
    }

    /**
     * @return \Iterator<int|string, mixed>
     */
    public function getQueryStrings(): Iterator
    {
        yield [
            'kod = \'JAN\'',
            'kod eq \'JAN\'',
        ];

        yield [
            'kod=\'=JAN\'',
            'kod eq \'=JAN\'',
        ];

        yield [
            'kod=\'=JAN=<>\'',
            'kod eq \'=JAN=<>\'',
        ];

        yield [
            'kod<>\'JAN\'',
            'kod neq \'JAN\'',
        ];

        yield [
            'datSplat<\'2018-12-04\'%20and%20zuctovano=false',
            'datSplat lt \'2018-12-04\' and zuctovano eq false',
        ];

        yield [
            'datSplat<\'2018-12-04\' and zuctovano=false',
            'datSplat lt \'2018-12-04\' and zuctovano eq false',
        ];

        yield [
            'sparovano = true and lastUpdate <= \'2018-12-11\'',
            'sparovano eq true and lastUpdate lte \'2018-12-11\'',
        ];
    }

}
