<?php

declare(strict_types=1);

// Checks the syntax of every PHP file of the repository on the running PHP version, so CI
// catches syntax that only a newer PHP version accepts. Run it with `composer lint`.

$root = dirname(__DIR__);
$files = [];
foreach (['src', 'tests', 'examples', 'scripts'] as $dir) {
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $dir, FilesystemIterator::SKIP_DOTS));
    foreach ($items as $item) {
        if ($item->getExtension() === 'php') {
            $files[] = $item->getPathname();
        }
    }
}
$files[] = $root . '/.php-cs-fixer.dist.php';
sort($files);

$failed = 0;
foreach ($files as $file) {
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $status);
    if ($status !== 0) {
        fwrite(STDERR, implode("\n", $output) . "\n");
        ++$failed;
    }
    $output = [];
}
if ($failed > 0) {
    fwrite(STDERR, "$failed of " . count($files) . " files have syntax errors\n");
    exit(1);
}
echo count($files) . ' PHP files have valid syntax on PHP ' . PHP_VERSION . "\n";
