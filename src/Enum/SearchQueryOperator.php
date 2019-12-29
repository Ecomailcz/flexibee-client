<?php declare(strict_types = 1);

namespace EcomailFlexibee\Enum;

use Consistence\Enum\Enum;

class SearchQueryOperator extends Enum
{

    /**
     * @var array<mixed>
     */
    private static $operators = [
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
        $query = \urldecode($query);
        /** @var array<mixed> $queryExploded */
        $queryExploded  = \preg_split('/\s+/', $query);

        foreach($queryExploded as &$part) {
            $toReplace = [];
            \preg_match_all('/[^\'](?=(?:[^\']*\'[^"]*\'[^\']*|[^\'])*$)/', $part, $matches);

            if (isset($matches[0])) {
                $text = \implode('', $matches[0]);
                $toReplace[$text] = \str_replace(\array_keys(self::$operators), \array_values(self::$operators), $text);
            }

            $part = \str_replace(\array_keys($toReplace), \array_values($toReplace), $part);
        }

        /** @var string $result */
        $result = \preg_replace('/\s+/', ' ', \implode(' ',$queryExploded));

        return $result;
    }

}
