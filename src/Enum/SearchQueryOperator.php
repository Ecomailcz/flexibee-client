<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Enum;

use function array_keys;
use function array_values;
use function implode;
use function preg_match_all;
use function preg_replace;
use function preg_split;
use function str_replace;
use function urldecode;

class SearchQueryOperator
{

    /**
     * @var array<mixed>
     */
    private static array $operators = [
        '==' => ' eq ',
        '<=' => ' lte ',
        '<>' => ' neq ',
        '>=' => ' gte ',
        '!=' => ' neq ',
        '=' => ' eq ',
        '<' => ' lt ',
        '>' => ' gt ',
    ];

    public static function convertOperatorsInQuery(string $query): string
    {
        $query = urldecode($query);
        /** @var array<string> $queryExploded */
        $queryExploded = preg_split('/\s+/', $query);

        foreach($queryExploded as &$part) {
            $toReplace = [];
            preg_match_all('/[^\'](?=(?:[^\']*\'[^"]*\'[^\']*|[^\'])*$)/', $part, $matches);

            if (isset($matches[0])) {
                $text = implode('', $matches[0]);
                $toReplace[$text] = str_replace(array_keys(self::$operators), array_values(self::$operators), $text);
            }

            $part = str_replace(array_keys($toReplace), array_values($toReplace), $part);
        }

        /** @var string $result */
        $result = preg_replace('/\s+/', ' ', implode(' ',$queryExploded));

        return $result;
    }

}
