<?php

declare(strict_types=1);

namespace EasySlip\Tests\Unit;

use EasySlip\Exception\AuthenticationError;
use EasySlip\Exception\RateLimitError;
use EasySlip\Tests\Support\SequenceClient;
use EasySlip\Transport\Transport;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class RetryContractTest extends TestCase
{
    public function testBilled429RetriesWithExactDelay(): void
    {
        $client = new SequenceClient([new Response(429, ['Retry-After' => '1']), new Response(200)]);
        $delays = [];
        $transport = new Transport($client, new HttpFactory(), 'secret', sleep: function (float $delay) use (&$delays): void {
            $delays[] = $delay;
        });
        self::assertSame(200, $transport->send('POST', 'https://example.test/verify')->getStatusCode());
        self::assertSame([1.0], $delays);
    }

    public function testLongRetryAfterDoesNotSleep(): void
    {
        $client = new SequenceClient([new Response(429, ['Retry-After' => '61'])]);
        $transport = new Transport($client, new HttpFactory(), 'secret', sleep: static function (): void {
            self::fail('Must not sleep');
        });
        $this->expectException(RateLimitError::class);
        $transport->send('GET', 'https://example.test/info', safe: true);
    }

    public function testNestedCodeWinsOverHttpStatus(): void
    {
        $client = new SequenceClient([new Response(400, [], '{"error":{"code":"INVALID_API_KEY","message":"No access"}}')]);
        $this->expectException(AuthenticationError::class);
        (new Transport($client, new HttpFactory(), 'secret'))->send('GET', 'https://example.test/info');
    }
}
