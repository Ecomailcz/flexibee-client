<?php

declare(strict_types = 1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    // The package supports PHP 8.2+: rules that need a newer PHP version are skipped.
    ->withPhpVersion(PhpVersion::PHP_82)
    ->withSets([__DIR__ . '/vendor/pekral/rector-rules/rector.php']);
