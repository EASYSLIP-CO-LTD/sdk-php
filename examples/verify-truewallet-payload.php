<?php

declare(strict_types=1);

// Verifies a TrueMoney Wallet transfer from the text of its QR code.
// Usage: php examples/verify-truewallet-payload.php <QR payload>

use EasySlip\Client;

require __DIR__ . '/bootstrap.php';

$payload = arg(0, '<QR payload>');

run(fn(): mixed => (new Client())->verify->truewalletByPayload($payload));
