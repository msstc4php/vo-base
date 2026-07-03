<?php

declare(strict_types=1);

use MSSTC4PHP\ValueObject\Tests\Support\IsoEnumGenerator;

require __DIR__ . '/../vendor/autoload.php';

$root = dirname(__DIR__);
$generator = new IsoEnumGenerator();

foreach ($generator->all() as $relativePath => $content) {
    $target = $root . '/' . $relativePath;
    file_put_contents($target, $content);
    echo "wrote {$relativePath}\n";
}
