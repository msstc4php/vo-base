<?php

declare(strict_types=1);

use MSSTC4PHP\ValueObject\Tests\Support\IbanRegistryGenerator;

require __DIR__ . '/../vendor/autoload.php';

$source = $argv[1] ?? null;
$edition = $argv[2] ?? null;
if ($source === null || $edition === null || $edition === '') {
    fwrite(STDERR, "Usage: php tools/generate-iban-registry.php <iban-registry.txt> <edition label>\n");
    exit(1);
}

$registry = file_get_contents($source);
if ($registry === false) {
    fwrite(STDERR, "Cannot read {$source}\n");
    exit(1);
}

$target = dirname(__DIR__) . '/src/Support/IbanRegistry.php';
file_put_contents($target, (new IbanRegistryGenerator())->render($registry, $edition));
echo "wrote src/Support/IbanRegistry.php\n";
