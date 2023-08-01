<?php

declare(strict_types = 1);

use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
    ]);

    foreach (include(__DIR__.'/vendor/pekral/rector-rules/rules/rules.php') as $ruleClassName) {
        $rectorConfig->rule($ruleClassName);
    }
};