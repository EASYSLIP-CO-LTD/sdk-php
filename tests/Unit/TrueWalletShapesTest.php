<?php

declare(strict_types=1);

namespace EasySlip\Tests\Unit;

use EasySlip\Client;
use EasySlip\Exception\DuplicateSlipError;
use EasySlip\Exception\InputError;
use EasySlip\Input\{V1VerifyOptions, VerifyOptions};
use EasySlip\Model\TrueWalletSlip;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class TrueWalletShapesTest extends TestCase
{
    private const BASE = ['transactionId' => '50012345678902', 'date' => '2026-12-03T22:05:00+07:00', 'amount' => 200.25];

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function shapes(): iterable
    {
        yield 'names' => [self::BASE + ['sender' => ['name' => 'นาย ผู้โอน ทดสอบ'], 'receiver' => ['name' => 'นาย รับเงิน ทดสอบ', 'phone' => '08-xxx-4567']]];
        yield 'phones' => [self::BASE + ['sender' => ['phone' => '08-xxx-2345'], 'receiver' => ['phone' => '08-xxx-9876']]];
        yield 'receiver phone only' => [self::BASE + ['receiver' => ['phone' => '08-xxx-9876']]];
        yield 'no parties' => [self::BASE];
    }

    /** @param array<string, mixed> $raw */
    #[DataProvider('shapes')]
    public function testSlipReadsEveryShapeAndRoundTrips(array $raw): void
    {
        $slip = new TrueWalletSlip($raw);
        self::assertSame($raw, $slip->jsonSerialize());
        self::assertSame(isset($raw['sender']), $slip->sender !== null);
        self::assertSame(isset($raw['receiver']), $slip->receiver !== null);
    }

    public function testPhoneOnlyPartyHasNoName(): void
    {
        $slip = new TrueWalletSlip(self::BASE + ['sender' => ['phone' => '08-xxx-2345']]);
        self::assertNull($slip->sender?->name);
        self::assertFalse($slip->sender->has('name'));
        self::assertSame('08-xxx-2345', $slip->sender->phone);
    }

    public function testV2PayloadSendsJsonAndReadsNoParties(): void
    {
        $http = $this->http(200, ['success' => true, 'data' => ['amountInSlip' => 200.25, 'rawSlip' => self::BASE]]);
        $client = $this->client($http);
        $result = $client->verify->truewalletByPayload('wallet-qr', new VerifyOptions(remark: 'r', checkDuplicate: true));
        self::assertSame('/verify/truewallet', $http->request?->getUri()->getPath());
        self::assertSame(['payload' => 'wallet-qr', 'remark' => 'r', 'checkDuplicate' => true], json_decode((string) $http->request->getBody(), true));
        self::assertNull($result->rawSlip->sender);
        self::assertNull($result->rawSlip->receiver);
    }

    public function testV1PayloadSendsAJsonBooleanAndReadsPhones(): void
    {
        $raw = self::BASE + ['sender' => ['phone' => '08-xxx-2345'], 'receiver' => ['phone' => '08-xxx-9876']];
        $http = $this->http(200, ['status' => 200, 'data' => $raw]);
        $slip = $this->client($http)->v1->verifyTruemoneyByPayload('wallet-qr', new V1VerifyOptions(checkDuplicate: false));
        self::assertSame(['payload' => 'wallet-qr', 'checkDuplicate' => false], json_decode((string) $http->request?->getBody(), true));
        self::assertSame('08-xxx-9876', $slip->receiver?->phone);
    }

    public function testV1DuplicateWithoutNamesKeepsTheSlip(): void
    {
        $raw = self::BASE + ['sender' => ['phone' => '08-xxx-2345']];
        $http = $this->http(400, ['status' => 400, 'message' => 'duplicate_slip', 'data' => $raw]);
        try {
            $this->client($http)->v1->verifyTruemoneyByPayload('wallet-qr', new V1VerifyOptions(checkDuplicate: true));
            self::fail('Expected a duplicate');
        } catch (DuplicateSlipError $error) {
            self::assertInstanceOf(TrueWalletSlip::class, $error->slip);
            self::assertSame($raw, $error->slip->jsonSerialize());
        }
    }

    public function testBadPayloadIsRefusedBeforeAnyRequest(): void
    {
        $http = $this->http(200, []);
        $client = $this->client($http);
        foreach (['', str_repeat('x', 257)] as $payload) {
            foreach ([fn() => $client->verify->truewalletByPayload($payload), fn() => $client->v1->verifyTruemoneyByPayload($payload)] as $call) {
                try {
                    $call();
                    self::fail('Expected InputError');
                } catch (InputError) {
                    self::assertNull($http->request);
                }
            }
        }
    }

    /** @param array<string, mixed> $body */
    private function http(int $status, array $body): ClientInterface
    {
        return new class ($status, $body) implements ClientInterface {
            public ?RequestInterface $request = null;
            /** @param array<string, mixed> $body */
            public function __construct(private int $status, private array $body) {}
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->request = $request;
                return new Response($this->status, ['Content-Type' => 'application/json'], json_encode($this->body, JSON_THROW_ON_ERROR));
            }
        };
    }

    private function client(ClientInterface $http): Client
    {
        return new Client(apiKey: 'fixture-key', v1BaseUrl: 'https://unit.test', v2BaseUrl: 'https://unit.test', maxRetries: 0, httpClient: $http);
    }
}
