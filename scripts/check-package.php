<?php

declare(strict_types=1);

// Builds the package archive and checks it the way a customer receives it:
// 1. composer.json is valid (composer validate --strict)
// 2. the archive is `git archive HEAD`, the same zip GitHub serves to Composer for a tag: it
//    holds src, composer.json, the READMEs, LICENSE and CHANGELOG, and no tests or tooling
// 3. a fresh project installs the archive and makes an offline call, then calls a local test
//    server with both real HTTP clients (Guzzle and Symfony)
// The archive is built from the committed HEAD, so commit your changes first.
// Usage: php scripts/check-package.php [--out <dir>] [--keep <dir>]
//   --out   copy the archive to <dir> (the release workflow attaches it to the GitHub Release)
//   --keep  build the consumer project in <dir> and leave it there

$root = dirname(__DIR__);
$options = getopt('', ['out:', 'keep:']);
$out = is_string($options['out'] ?? null) ? $options['out'] : null;
$keep = is_string($options['keep'] ?? null) ? $options['keep'] : null;
$composer = getenv('COMPOSER_BINARY') ?: 'composer';
$composerCommand = str_ends_with($composer, '.phar') ? [PHP_BINARY, $composer] : [$composer];

/** @param list<string> $command */
function run(array $command, string $cwd): string
{
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd);
    if ($process === false) {
        throw new RuntimeException('cannot start ' . $command[0]);
    }
    $stdout = (string) stream_get_contents($pipes[1]);
    $stderr = (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        fwrite(STDERR, $stdout . $stderr);
        throw new RuntimeException(implode(' ', $command) . ' failed');
    }
    return $stdout;
}

function removeTree(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($items as $item) {
        $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
}

$manifest = json_decode((string) file_get_contents($root . '/.release-please-manifest.json'), true, 512, JSON_THROW_ON_ERROR);
$version = $manifest['.'];
$work = sys_get_temp_dir() . '/easyslip-sdk-package-' . bin2hex(random_bytes(4));
mkdir($work);
try {
    // 1. composer.json
    run([...$composerCommand, 'validate', '--strict', '--no-check-publish'], $root);
    echo "composer validate --strict: ok\n";

    // 2. The archive and what is inside.
    $zip = $work . '/easyslip-sdk-' . $version . '.zip';
    run(['git', 'archive', '--format=zip', '--output=' . $zip, 'HEAD'], $root);
    $files = array_values(array_filter(explode("\n", run(['unzip', '-Z1', $zip], $root)), static fn(string $f): bool => $f !== '' && !str_ends_with($f, '/')));
    sort($files);
    foreach (['composer.json', 'README.md', 'README.th.md', 'LICENSE', 'CHANGELOG.md', 'src/Client.php', 'src/EasySlip.php'] as $want) {
        if (!in_array($want, $files, true)) {
            throw new RuntimeException('the archive is missing ' . $want);
        }
    }
    $stray = array_filter($files, static fn(string $f): bool => preg_match('#^(src/|composer\.json$|README(\.th)?\.md$|LICENSE$|CHANGELOG\.md$)#', $f) !== 1);
    if ($stray !== []) {
        throw new RuntimeException('the archive must not contain ' . implode(', ', $stray));
    }
    echo 'archive ' . basename($zip) . ': ' . count($files) . ' files, ' . filesize($zip) . " bytes\n";

    // 3. A fresh consumer project installs the archive, the way Packagist describes it: the
    // package's own composer.json plus the version and the zip.
    $package = json_decode((string) run(['unzip', '-p', $zip, 'composer.json'], $root), true, 512, JSON_THROW_ON_ERROR);
    $package['version'] = $version;
    $package['dist'] = ['type' => 'zip', 'url' => $zip];
    $app = $keep ?? $work . '/app';
    if (!is_dir($app)) {
        mkdir($app, 0o777, true);
    }
    // The package itself comes from the zip; its dependencies, and Symfony's client for the
    // adapter check, come from Packagist.
    file_put_contents($app . '/composer.json', json_encode([
        'name' => 'example/consumer',
        'type' => 'project',
        'repositories' => [['type' => 'package', 'package' => $package]],
        'require' => ['easyslip/sdk' => $version, 'symfony/http-client' => '*'],
        'config' => ['allow-plugins' => ['php-http/discovery' => true]],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
    run([...$composerCommand, 'install', '--no-interaction', '--no-progress', '--prefer-dist', '--no-dev'], $app);
    $installed = json_decode((string) file_get_contents($app . '/vendor/composer/installed.json'), true, 512, JSON_THROW_ON_ERROR);
    $sdk = array_values(array_filter($installed['packages'], static fn(array $p): bool => $p['name'] === 'easyslip/sdk'))[0] ?? null;
    if ($sdk === null || ($sdk['dist']['url'] ?? '') !== $zip) {
        throw new RuntimeException('easyslip/sdk was not installed from the archive');
    }
    if (is_dir($app . '/vendor/easyslip/sdk/tests')) {
        throw new RuntimeException('the installed package contains tests');
    }
    echo 'fresh project: installed easyslip/sdk ' . $version . " from the archive\n";
    echo 'offline call: ' . run([PHP_BINARY, __DIR__ . '/consumer/offline-call.php'], $app);
    echo run([PHP_BINARY, __DIR__ . '/consumer/adapters.php'], $app);

    if ($out !== null) {
        if (!is_dir($out)) {
            mkdir($out, 0o777, true);
        }
        copy($zip, $out . '/' . basename($zip));
        echo 'copied the archive to ' . $out . '/' . basename($zip) . "\n";
    }
    echo 'ok easyslip/sdk ' . $version . ' on PHP ' . PHP_VERSION . "\n";
} finally {
    removeTree($work);
}
