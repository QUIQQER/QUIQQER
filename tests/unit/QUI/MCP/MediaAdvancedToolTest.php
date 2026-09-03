<?php

declare(strict_types=1);

namespace QUI\MCP;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\MCP\Project\Media\AbstractMediaTool;
use ReflectionMethod;

class MediaAdvancedToolTest extends TestCase
{
    public function testImageEffectsAreNormalized(): void
    {
        $Method = new ReflectionMethod(AbstractMediaTool::class, 'normalizeMediaEffectUpdates');
        $effects = $Method->invoke(null, [
            'blur' => 25,
            'brightness' => -10,
            'contrast' => 40,
            'greyscale' => true,
            'watermark' => 'default',
            'watermark_position' => 'bottom-right',
            'watermark_ratio' => 35
        ]);

        self::assertSame([
            'blur' => 25,
            'brightness' => -10,
            'contrast' => 40,
            'greyscale' => 1,
            'watermark' => 'default',
            'watermark_position' => 'bottom-right',
            'watermark_ratio' => 35
        ], $effects);
    }

    public function testNullImageEffectsAreAcceptedForInheritance(): void
    {
        $Method = new ReflectionMethod(AbstractMediaTool::class, 'normalizeMediaEffectUpdates');

        self::assertSame([
            'blur' => null,
            'greyscale' => null,
            'watermark' => null,
            'watermark_position' => null,
            'watermark_ratio' => null
        ], $Method->invoke(null, [
            'blur' => null,
            'greyscale' => null,
            'watermark' => null,
            'watermark_position' => null,
            'watermark_ratio' => null
        ]));
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidEffectsProvider(): iterable
    {
        yield 'unknown effect' => [['sepia' => 1]];
        yield 'blur too large' => [['blur' => 101]];
        yield 'brightness is not an integer' => [['brightness' => '20']];
        yield 'invalid greyscale value' => [['greyscale' => 2]];
        yield 'invalid watermark ID' => [['watermark' => 0]];
        yield 'invalid watermark position' => [['watermark_position' => 'somewhere']];
        yield 'invalid watermark ratio' => [['watermark_ratio' => 0]];
    }

    /**
     * @param array<string, mixed> $effects
     */
    #[DataProvider('invalidEffectsProvider')]
    public function testInvalidImageEffectsAreRejected(array $effects): void
    {
        $Method = new ReflectionMethod(AbstractMediaTool::class, 'normalizeMediaEffectUpdates');

        $this->expectException(QUI\Exception::class);
        $Method->invoke(null, $effects);
    }

    public function testEmptyImageEffectsAreRejected(): void
    {
        $Method = new ReflectionMethod(AbstractMediaTool::class, 'normalizeMediaEffectUpdates');

        $this->expectException(QUI\Exception::class);
        $Method->invoke(null, []);
    }
}
