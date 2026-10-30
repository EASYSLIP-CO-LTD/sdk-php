<?php

declare(strict_types=1);

// Walks every page of all accounts of the service, optionally of one type.
// Usage: php examples/bank-accounts-iterate-all.php [NATURAL|JURISTIC]

use EasySlip\Client;
use EasySlip\Enum\AccountType;
use EasySlip\Input\ListBankAccountsParams;

require __DIR__ . '/bootstrap.php';

$type = optionalArg(0);

run(function () use ($type): mixed {
    $params = new ListBankAccountsParams(type: $type === null ? null : AccountType::from($type));
    $count = 0;
    foreach ((new Client())->bankAccounts->iterateAll($params) as $account) {
        echo $account->id, ' ', $account->type, ' ', $account->bankNumber, ' ', $account->nameEn, PHP_EOL;
        ++$count;
    }
    return ['count' => $count];
});
