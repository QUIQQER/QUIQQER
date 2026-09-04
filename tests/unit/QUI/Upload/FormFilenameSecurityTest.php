<?php

declare(strict_types=1);

namespace QUI\Upload;

use DOMDocument;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function strpos;
use function substr;

final class FormFilenameSecurityTest extends TestCase
{
    private const ATTACK_FILENAME = '<img src=x onerror="alert(1)"><script>alert(2)</script>.txt';

    public function testSelectedFilenamesAreWrittenThroughTextContent(): void
    {
        $source = (string)file_get_contents(
            dirname(__DIR__, 4) . '/bin/QUI/controls/upload/Form.js'
        );
        $singleView = $this->getMethod($source, '$createSingleView: function', '$imagePreview: function');
        $addUpload = $this->getMethod($source, 'addUpload: function', 'cleanup: function');

        self::assertStringContainsString(
            ').textContent = Input.files[0].name;',
            $singleView
        );
        self::assertStringNotContainsString(".set('html', Input.files[0].name)", $singleView);
        self::assertStringContainsString('FileInfo.textContent = File.name;', $addUpload);
        self::assertStringNotContainsString("FileInfo.set('html', File.name)", $addUpload);
    }

    public function testAttackFilenameRemainsText(): void
    {
        $Document = new DOMDocument('1.0', 'UTF-8');
        $Filename = $Document->createElement('span');
        $Filename->textContent = self::ATTACK_FILENAME;

        self::assertSame(self::ATTACK_FILENAME, $Filename->textContent);
        self::assertSame(0, $Filename->getElementsByTagName('img')->length);
        self::assertSame(0, $Filename->getElementsByTagName('script')->length);
    }

    private function getMethod(string $source, string $startMarker, string $endMarker): string
    {
        $methodStart = strpos($source, $startMarker);

        self::assertIsInt($methodStart);

        $methodEnd = strpos($source, $endMarker, $methodStart);

        self::assertIsInt($methodEnd);

        return substr($source, $methodStart, $methodEnd - $methodStart);
    }
}
