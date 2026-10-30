<?php

declare(strict_types=1);

// v1: verifies a TrueMoney Wallet slip image.
// Usage: php examples/v1-verify-truemoney-image.php <image file>

use EasySlip\Client;
use EasySlip\Input\FileInput;
use EasySlip\Input\V1VerifyOptions;

require __DIR__ . '/bootstrap.php';

$path = arg(0, '<image file>');

run(fn(): mixed => (new Client())->v1->verifyTruemoneyByImage(FileInput::fromPath($path), new V1VerifyOptions(checkDuplicate: true)));
