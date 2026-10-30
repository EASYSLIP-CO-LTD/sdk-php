<?php

declare(strict_types=1);

namespace EasySlip\Tests\Unit;

use EasySlip\Exception\RequestTimeoutError;
use EasySlip\Transport\Transport;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class TimeoutTest extends TestCase
{
    public function testStreamHandlerTimeoutIsClassified(): void
    {
        $client = new class implements ClientInterface {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                throw new ConnectException('Operation timed out', $request);
            }
        };
        $this->expectException(RequestTimeoutError::class);
        (new Transport($client, new HttpFactory(), 'test-key'))->send('POST', 'https://example.test/billed');
    }

    public function testDefaultClientTimeoutHasItsOwnSanitizedTypeAndNoBilledRetry(): void
    {
        $client = new class implements ClientInterface {
            public int $calls = 0;
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                ++$this->calls;
                throw new ConnectException('Operation timed out: credential-should-not-escape', $request);
            }
        };
        $transport = new Transport($client, new HttpFactory(), 'credential-should-not-escape');
        try {
            $transport->send('POST', 'https://example.test/billed');
            self::fail('Expected timeout');
        } catch (RequestTimeoutError $error) {
            self::assertInstanceOf(\EasySlip\Transport\SanitizedCause::class, $error->getPrevious());
            self::assertNull($error->getPrevious()->getPrevious());
            self::assertStringNotContainsString('credential-should-not-escape', (string) $error);
        }
        self::assertSame(1, $client->calls);
    }
}
