<?php

declare(strict_types=1);

namespace QUI\MCP;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\MCP\Project\Media\AbstractMediaTool;
use QUI\MCP\Project\Trash\AbstractTrashTool;
use ReflectionMethod;

class MediaTrashToolTest extends TestCase
{
    public function testTrashIdsAreValidatedAndDeduplicated(): void
    {
        $Method = new ReflectionMethod(AbstractTrashTool::class, 'validateIds');

        self::assertSame([4, 8], $Method->invoke(null, [4, 8, 4]));
    }

    public function testTrashIdsRejectInvalidValues(): void
    {
        $Method = new ReflectionMethod(AbstractTrashTool::class, 'validateIds');

        $this->expectException(QUI\Exception::class);
        $Method->invoke(null, [1, '2']);
    }

    public function testPermanentDeletionRequiresExplicitConfirmation(): void
    {
        $Method = new ReflectionMethod(AbstractTrashTool::class, 'requireConfirmation');

        $this->expectException(QUI\Exception::class);
        $Method->invoke(null, false);
    }

    public function testDownloadIsReturnedAsBase64WithinLimit(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'mcp-download-');
        self::assertNotFalse($path);

        try {
            self::assertSame(12, file_put_contents($path, 'MCP download'));
            $Method = new ReflectionMethod(AbstractMediaTool::class, 'readDownload');
            $result = $Method->invoke(null, $path, 'test.txt', 'text/plain', 12);

            self::assertSame('test.txt', $result['filename']);
            self::assertSame('text/plain', $result['mimeType']);
            self::assertSame(12, $result['size']);
            self::assertSame('base64', $result['encoding']);
            self::assertSame('MCP download', base64_decode($result['contentBase64'], true));
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testDownloadOverLimitIsRejected(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'mcp-download-');
        self::assertNotFalse($path);

        try {
            self::assertSame(4, file_put_contents($path, 'test'));
            $Method = new ReflectionMethod(AbstractMediaTool::class, 'readDownload');

            $this->expectException(QUI\Exception::class);
            $Method->invoke(null, $path, 'test.txt', 'text/plain', 3);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
