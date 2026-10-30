<?php

declare(strict_types=1);

// Calls a local test server through an installed copy of the package with both real HTTP
// clients (Guzzle and Symfony): a plain call, a read retried after a 500, and the time limit of
// waitForJob. Exits with an error when anything differs.
// Usage (from the consumer project): php path/to/adapters.php

use EasySlip\Client;
use EasySlip\Exception\JobWaitTimeoutError;
use EasySlip\Input\WaitOptions;

require getcwd() . '/vendor/autoload.php';

foreach (['guzzle', 'symfony'] as $adapter) {
    $socket = stream_socket_server('tcp://127.0.0.1:0');
    if ($socket === false) {
        throw new RuntimeException('Cannot open a local port');
    }
    $address = (string) stream_socket_get_name($socket, false);
    fclose($socket);
    $state = (string) tempnam(sys_get_temp_dir(), 'easyslip-consumer-');
    $server = proc_open([PHP_BINARY, '-S', $address, __DIR__ . '/loopback-server.php'], [['pipe', 'r'], ['file', '/dev/null', 'w'], ['file', '/dev/null', 'w']], $pipes, null, ['EASYSLIP_CONSUMER_STATE' => $state]);
    if ($server === false) {
        throw new RuntimeException('Cannot start the local server');
    }
    fclose($pipes[0]);
    try {
        for ($i = 0; $i < 100; ++$i) {
            $ready = @stream_socket_client('tcp://' . $address, $errno, $message, 0.01);
            if ($ready !== false) {
                fclose($ready);
                break;
            }
            usleep(10000);
        }
        $http = $adapter === 'guzzle' ? new GuzzleHttp\Client() : new Symfony\Component\HttpClient\Psr18Client();
        foreach (['info', 'retry'] as $operation) {
            $client = new Client(apiKey: 'synthetic-consumer', v2BaseUrl: 'http://' . $address . '/' . $operation, httpClient: $http, sleep: static function (float $seconds): void {});
            if ($client->getInfo()->application->name !== 'consumer-smoke') {
                throw new RuntimeException('Unexpected info response');
            }
            if ($operation === 'retry' && (int) file_get_contents($state) !== 2) {
                throw new RuntimeException('Expected exactly two sends for a read retried after a 500');
            }
            echo "$adapter $operation ok\n";
        }
        $client = new Client(apiKey: 'synthetic-consumer', v2BaseUrl: 'http://' . $address . '/info', httpClient: $http);
        $start = hrtime(true);
        try {
            $client->jobs->waitForJob('slow', new WaitOptions(timeout: 0.02));
            throw new RuntimeException('The waitForJob time limit was not enforced');
        } catch (JobWaitTimeoutError) {
            $elapsed = (hrtime(true) - $start) / 1e9;
            if ($elapsed < 0.014 || $elapsed > 0.12) {
                throw new RuntimeException('waitForJob stopped after ' . $elapsed . ' s, want about 0.02 s');
            }
            echo "$adapter waitForJob time limit ok (" . round($elapsed * 1000, 1) . " ms)\n";
        }
    } finally {
        proc_terminate($server);
        proc_close($server);
        unlink($state);
    }
}
