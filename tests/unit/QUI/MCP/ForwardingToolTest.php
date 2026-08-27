<?php

declare(strict_types=1);

namespace QUI\MCP;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\MCP\Forwarding\AbstractForwardingTool;
use ReflectionMethod;

class ForwardingToolTest extends TestCase
{
    /**
     * @return iterable<string, array{int|string, int}>
     */
    public static function validHttpCodeProvider(): iterable
    {
        yield 'integer' => [301, 301];
        yield 'numeric string' => ['308', 308];
    }

    #[DataProvider('validHttpCodeProvider')]
    public function testHttpStatusCodeIsNormalized(int | string $input, int $expected): void
    {
        $Method = new ReflectionMethod(AbstractForwardingTool::class, 'normalizeHttpCode');

        self::assertSame($expected, $Method->invoke(null, $input));
    }

    public function testUnsupportedHttpStatusCodeIsRejected(): void
    {
        $Method = new ReflectionMethod(AbstractForwardingTool::class, 'normalizeHttpCode');

        $this->expectException(QUI\Exception::class);
        $Method->invoke(null, 200);
    }

    public function testNonNumericHttpStatusCodeIsRejected(): void
    {
        $Method = new ReflectionMethod(AbstractForwardingTool::class, 'normalizeHttpCode');

        $this->expectException(QUI\Exception::class);
        $Method->invoke(null, 'redirect');
    }

    public function testForwardingDataUsesPublicFieldNames(): void
    {
        $Method = new ReflectionMethod(AbstractForwardingTool::class, 'parseForwarding');

        self::assertSame([
            'source' => '/old',
            'target' => '/new',
            'httpCode' => 307
        ], $Method->invoke(null, '/old', ['target' => '/new', 'code' => '307']));
    }

    public function testSourcesAreTrimmedAndDeduplicated(): void
    {
        $Method = new ReflectionMethod(AbstractForwardingTool::class, 'normalizeSources');

        self::assertSame(['/one', '/two'], $Method->invoke(null, [
            ' /one ',
            '/two',
            '/one'
        ]));
    }

    public function testEmptySourceListIsRejected(): void
    {
        $Method = new ReflectionMethod(AbstractForwardingTool::class, 'normalizeSources');

        $this->expectException(QUI\Exception::class);
        $Method->invoke(null, []);
    }

    public function testNonStringSourceIsRejected(): void
    {
        $Method = new ReflectionMethod(AbstractForwardingTool::class, 'normalizeSources');

        $this->expectException(QUI\Exception::class);
        $Method->invoke(null, [12]);
    }

    public function testEmptySourceIsRejected(): void
    {
        $Method = new ReflectionMethod(AbstractForwardingTool::class, 'normalizeSource');

        $this->expectException(QUI\Exception::class);
        $Method->invoke(null, '   ');
    }

    public function testTargetIsTrimmedAndMayBeEmpty(): void
    {
        $Method = new ReflectionMethod(AbstractForwardingTool::class, 'normalizeTarget');

        self::assertSame('/target', $Method->invoke(null, ' /target '));
        self::assertSame('', $Method->invoke(null, '   '));
    }
}
