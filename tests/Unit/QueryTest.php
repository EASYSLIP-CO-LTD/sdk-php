<?php

declare(strict_types=1);

namespace EasySlip\Tests\Unit;

use EasySlip\Transport\Query;
use PHPUnit\Framework\TestCase;

final class QueryTest extends TestCase
{
    public function testBooleanQueryUsesLiteralWords(): void
    {
        self::assertSame('enabled=true&duplicate=false&limit=0&name=a%20b', Query::encode([
            'enabled' => true, 'duplicate' => false, 'limit' => 0, 'name' => 'a b', 'missing' => null,
        ]));
    }
}
