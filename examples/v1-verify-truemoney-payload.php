<?php

declare(strict_types=1);

// Verifies a TrueMoney Wallet transfer from the text of its QR code with API v1.
// Usage: php examples/v1-verify-truemoney-payload.php <QR payload>

use EasySlip\Client;

require __DIR__ . '/bootstrap.php';

$payload = arg(0, '<QR payload>');

run(fn(): mixed => (new Client())->v1->verifyTruemoneyByPayload($payload));
