<?php

declare(strict_types=1);

// The local test server of adapters.php, run with `php -S`.

header('Content-Type: application/json');
$path = (string) $_SERVER['REQUEST_URI'];
if (str_contains($path, '/jobs/')) {
    usleep(200000);
    echo '{"data":{"jobId":"slow","status":"done"}}';
    return;
}
if (str_contains($path, '/retry/')) {
    $file = (string) getenv('EASYSLIP_CONSUMER_STATE');
    $count = is_file($file) ? (int) file_get_contents($file) : 0;
    file_put_contents($file, (string) ($count + 1));
    if ($count === 0) {
        http_response_code(500);
        echo '{"error":{"message":"retry"}}';
        return;
    }
}
$fixture = json_decode((string) file_get_contents(__DIR__ . '/../../tests/fixtures/v2/info/success.json'), true, flags: JSON_THROW_ON_ERROR);
$body = $fixture['response']['body'];
$body['data']['application']['name'] = 'consumer-smoke';
echo json_encode($body, JSON_THROW_ON_ERROR);
