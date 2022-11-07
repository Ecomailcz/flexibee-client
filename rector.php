<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src'
    ]);
    foreach (include(__DIR__.'/vendor/pekral/rulezilla/src/Rules/Php/Rector/rector.php') as $ruleClassName) {
        $rectorConfig->rule($ruleClassName);
    }
};
