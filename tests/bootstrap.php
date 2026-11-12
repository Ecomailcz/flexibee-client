<?php

declare(strict_types = 1);

use Purl\AbstractPart;

require __DIR__ . '/../vendor/autoload.php';

$errorReporting = error_reporting(E_ALL & ~E_DEPRECATED);
class_exists(AbstractPart::class);
error_reporting($errorReporting);
