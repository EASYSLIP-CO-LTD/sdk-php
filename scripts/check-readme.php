<?php

declare(strict_types=1);

// Checks every ```php code block of README.md and README.th.md: each one is a complete file
// that starts with <?php, has valid syntax on this PHP version (php -l), and passes PHPStan at
// the max level against the SDK source. Run it with `composer check:readme`.

$root = dirname(__DIR__);
$dir = $root . '/build/readme-check';
if (!is_dir($dir . '/vendor')) {
    mkdir($dir . '/vendor', 0o777, true);
}
array_map(unlink(...), [...glob($dir . '/*.php') ?: [], ...glob($dir . '/*.neon') ?: []]);
// The blocks start with require __DIR__ . '/vendor/autoload.php', as in a customer's project.
file_put_contents($dir . '/vendor/autoload.php', "<?php\n\nreturn require " . var_export($root . '/vendor/autoload.php', true) . ";\n");

$count = 0;
$failed = false;
foreach (['README.md', 'README.th.md'] as $doc) {
    $text = (string) file_get_contents($root . '/' . $doc);
    preg_match_all('/^```php\n(.*?)^```$/ms', $text, $blocks, PREG_OFFSET_CAPTURE);
    foreach ($blocks[1] as $i => [$code, $offset]) {
        $line = substr_count(substr($text, 0, $offset), "\n") + 1;
        $name = str_replace('.', '-', strtolower($doc)) . '-' . ($i + 1) . '-line-' . $line . '.php';
        if (!str_starts_with($code, '<?php')) {
            fwrite(STDERR, "$doc:$line: the code block must start with <?php\n");
            $failed = true;
            continue;
        }
        file_put_contents($dir . '/' . $name, $code);
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($dir . '/' . $name) . ' 2>&1', $output, $status);
        if ($status !== 0) {
            fwrite(STDERR, "$doc:$line: " . implode("\n", $output) . "\n");
            $failed = true;
        }
        $output = [];
        ++$count;
    }
}
if ($failed) {
    exit(1);
}

file_put_contents($dir . '/phpstan.neon', <<<NEON
    parameters:
        level: max
        phpVersion:
            min: 80200
            max: 80599
        paths:
            - {$dir}
        excludePaths:
            - {$dir}/phpstan.neon
            - {$dir}/vendor
        tmpDir: {$root}/build/phpstan
    NEON);
passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/vendor/bin/phpstan') . ' analyse --no-progress --memory-limit=1G -c ' . escapeshellarg($dir . '/phpstan.neon'), $status);
if ($status !== 0) {
    fwrite(STDERR, "Fix the README code blocks above. Each file name gives the README and the line of the block.\n");
    exit(1);
}
echo "all $count README code blocks pass php -l and PHPStan level max\n";
