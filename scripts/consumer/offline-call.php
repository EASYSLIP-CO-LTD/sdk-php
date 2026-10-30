<?php

declare(strict_types=1);

// Makes an offline call through an installed copy of the package and checks what was sent.
// A fake PSR-18 client answers, so nothing leaves the machine. Prints one JSON line and exits
// with 1 when anything differs.
// Usage (from the consumer project): php path/to/offline-call.php

use EasySlip\Client;
use EasySlip\EasySlip;
use EasySlip\Exception\AuthenticationError;
use EasySlip\Exception\EasySlipError;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

require getcwd() . '/vendor/autoload.php';

$fixture = json_decode((string) file_get_contents(__DIR__ . '/../../tests/fixtures/v2/info/success.json'), true, 512, JSON_THROW_ON_ERROR);
$http = new class ($fixture['response']['body']) implements ClientInterface {
    /** @var list<RequestInterface> */
    public array $requests = [];

    /** @param array<string, mixed> $info */
    public function __construct(private array $info) {}

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;
        if ($request->getUri()->getPath() === '/v1/me') {
            return new Response(401, ['X-Request-Id' => 'req-offline-2'], '{"status":401,"message":"unauthorized"}');
        }
        return new Response(200, ['X-Request-Id' => 'req-offline-1', 'Content-Type' => 'application/json'], json_encode($this->info, JSON_THROW_ON_ERROR));
    }
};

$client = new EasySlip(apiKey: 'sk_offline_check_0000', httpClient: $http);
$info = $client->getInfo();
try {
    $client->v1->getMe();
    $rootError = false;
} catch (AuthenticationError $error) {
    $rootError = $error instanceof EasySlipError && $error->requestId === 'req-offline-2';
}
$sent = $http->requests[0];
$out = [
    'php' => PHP_VERSION,
    'url' => (string) $sent->getUri(),
    'auth' => $sent->getHeaderLine('Authorization'),
    'userAgent' => $sent->getHeaderLine('User-Agent'),
    'name' => $info->application->name,
    'requestId' => $info->meta->requestId,
    'alias' => $client instanceof Client,
    'rootError' => $rootError,
];
echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), PHP_EOL;
$want = [
    'url' => 'https://api.easyslip.com/v2/info',
    'auth' => 'Bearer sk_offline_check_0000',
    'name' => 'Example App',
    'requestId' => 'req-offline-1',
    'alias' => true,
    'rootError' => true,
];
foreach ($want as $key => $value) {
    if ($out[$key] !== $value) {
        fwrite(STDERR, $key . ' is ' . var_export($out[$key], true) . ', want ' . var_export($value, true) . PHP_EOL);
        exit(1);
    }
}
if (preg_match('#^easyslip-php/\d+\.\d+\.\d+ php/#', $out['userAgent']) !== 1) {
    fwrite(STDERR, 'unexpected User-Agent ' . $out['userAgent'] . PHP_EOL);
    exit(1);
}
