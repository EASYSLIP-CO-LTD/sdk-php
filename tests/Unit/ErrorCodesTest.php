<?php

declare(strict_types=1);

namespace EasySlip\Tests\Unit;

use EasySlip\Exception\EasySlipError;
use EasySlip\Exception\ErrorCodes;
use PHPUnit\Framework\TestCase;

/** The error tables match the API error catalogue in tests/fixtures/errors.json. */
final class ErrorCodesTest extends TestCase
{
    /** @return array<string, mixed> */
    private static function catalogue(): array
    {
        return json_decode((string) file_get_contents(__DIR__ . '/../fixtures/errors.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    public function testEveryApiErrorCodeMapsToTheCatalogueClass(): void
    {
        $want = [];
        foreach (['v2', 'v1'] as $version) {
            foreach (self::catalogue()['codes'][$version] as $code => $entry) {
                $want[$code] = 'EasySlip\\Exception\\' . $entry['class'];
            }
        }
        ksort($want);
        $got = ErrorCodes::CODES;
        ksort($got);
        self::assertSame($want, $got);
    }

    public function testUnknownCodesFallBackToTheClassForTheirStatus(): void
    {
        $want = [];
        foreach (self::catalogue()['statusFallback'] as $status => $class) {
            $want[(int) $status] = 'EasySlip\\Exception\\' . $class;
        }
        self::assertSame($want, ErrorCodes::STATUS);
    }

    public function testErrorClassesHaveTheCatalogueParents(): void
    {
        foreach (self::catalogue()['classes'] as $name => $entry) {
            $class = 'EasySlip\\Exception\\' . $name;
            self::assertTrue(class_exists($class), $name);
            $parent = $entry['parent'] === 'root' ? EasySlipError::class : 'EasySlip\\Exception\\' . $entry['parent'];
            self::assertSame($parent, get_parent_class($class), $name);
        }
    }
}
