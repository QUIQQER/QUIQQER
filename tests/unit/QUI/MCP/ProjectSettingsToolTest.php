<?php

declare(strict_types=1);

namespace QUI\MCP;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\MCP\Project\AbstractProjectSettingsTool;
use ReflectionMethod;

class ProjectSettingsToolTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function typeProvider(): iterable
    {
        yield 'bool' => ['bool', 'boolean'];
        yield 'boolean' => ['boolean', 'boolean'];
        yield 'int' => ['int', 'integer'];
        yield 'integer' => ['integer', 'integer'];
        yield 'float' => ['float', 'number'];
        yield 'number' => ['number', 'number'];
        yield 'text' => ['text', 'string'];
        yield 'unknown' => ['custom', 'string'];
    }

    #[DataProvider('typeProvider')]
    public function testTypesAreNormalized(string $input, string $expected): void
    {
        $Method = new ReflectionMethod(AbstractProjectSettingsTool::class, 'normalizeType');

        self::assertSame($expected, $Method->invoke(null, $input));
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function validValueProvider(): iterable
    {
        yield 'boolean' => [true, 'boolean'];
        yield 'integer' => [42, 'integer'];
        yield 'integer as number' => [42, 'number'];
        yield 'float' => [42.5, 'number'];
        yield 'string' => ['42', 'string'];
    }

    #[DataProvider('validValueProvider')]
    public function testMatchingSettingValuesAreAccepted(mixed $value, string $type): void
    {
        $Method = new ReflectionMethod(AbstractProjectSettingsTool::class, 'validateValue');

        $Method->invoke(null, 'example.setting', $value, $type);
        self::addToAssertionCount(1);
    }

    public function testMismatchingSettingValueIsRejected(): void
    {
        $Method = new ReflectionMethod(AbstractProjectSettingsTool::class, 'validateValue');

        $this->expectException(QUI\Exception::class);
        $this->expectExceptionMessage('expected integer');

        $Method->invoke(null, 'example.setting', '42', 'integer');
    }
}
