<?php declare(strict_types = 1);

namespace EcomailFlexibeeTest\Enum;

use EcomailFlexibee\Enum\SearchQueryOperator;
use PHPUnit\Framework\TestCase;

final class SearchQueryOperatorTest extends TestCase
{

    /**
     * @dataProvider getQueryStrings
     * @param string $query
     * @param string $expectedQuery
     */
    public function testConvertOperators(string $query, string $expectedQuery): void
    {
        $this->assertEquals($expectedQuery, SearchQueryOperator::convertOperatorsInQuery($query));
    }

    /**
     * @return array<mixed>
     */
    public function getQueryStrings(): array
    {
        return [
            [
                'kod = \'JAN\'',
                'kod eq \'JAN\'',
            ],
            [
                'kod=\'=JAN\'',
                'kod eq \'=JAN\'',
            ],
            [
                'kod=\'=JAN=<>\'',
                'kod eq \'=JAN=<>\'',
            ],
            [
                'kod<>\'JAN\'',
                'kod neq \'JAN\'',
            ],
            [
                'datSplat<\'2018-12-04\'%20and%20zuctovano=false',
                'datSplat lt \'2018-12-04\' and zuctovano eq false',
            ],
            [
                'datSplat<\'2018-12-04\' and zuctovano=false',
                'datSplat lt \'2018-12-04\' and zuctovano eq false',
            ],
            [
                'sparovano = true and lastUpdate <= \'2018-12-11\'',
                'sparovano eq true and lastUpdate lte \'2018-12-11\'',
            ],
        ];
    }

}
