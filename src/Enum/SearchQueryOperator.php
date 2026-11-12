<?php

declare(strict_types = 1);

namespace EcomailFlexibee\Enum;

use function array_map;
use function implode;
use function preg_match_all;
use function preg_replace;
use function preg_split;
use function str_replace;
use function strtr;
use function urldecode;

class SearchQueryOperator
{

    /**
     * @var array<string, string>
     */
    private static array $operators = [
        '!=' => ' neq ',
        '<' => ' lt ',
        '<=' => ' lte ',
        '<>' => ' neq ',
        '=' => ' eq ',
        '==' => ' eq ',
        '>' => ' gt ',
        '>=' => ' gte ',
    ];

    public static function convertOperatorsInQuery(string $query): string
    {
        $query = urldecode($query);
        $queryExploded = preg_split('/\s+/', $query);

        $converted = array_map(static function (string $part): string {
            preg_match_all('/[^\'](?=(?:[^\']*\'[^"]*\'[^\']*|[^\'])*$)/', $part, $matches);
            $text = implode('', $matches[0]);

            // strtr() always replaces the longest operator first ("<=" before "<"), independent of key order.
            return str_replace($text, strtr($text, self::$operators), $part);
        }, $queryExploded === false ? [] : $queryExploded);

        /** @var string $result */
        $result = preg_replace('/\s+/', ' ', implode(' ', $converted));

        return $result;
    }

}
