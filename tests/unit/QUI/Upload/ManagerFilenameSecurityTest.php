<?php

declare(strict_types=1);

namespace QUI\Upload;

use DOMDocument;
use DOMElement;
use PHPUnit\Framework\TestCase;

use function dirname;
use function file_get_contents;

final class ManagerFilenameSecurityTest extends TestCase
{
    private const ATTACK_FILENAME = '"><img src=x onerror="alert(1)"><script>alert(2)</script>.zip';

    public function testArchiveFilenamesAreWrittenThroughDomProperties(): void
    {
        $source = (string)file_get_contents(
            dirname(__DIR__, 4) . '/bin/QUI/controls/upload/Manager.js'
        );

        self::assertStringContainsString("const List = document.createElement('ul');", $source);
        self::assertStringContainsString("const Input = document.createElement('input');", $source);
        self::assertStringContainsString('Input.value = archiveFiles[i].name;', $source);
        self::assertStringContainsString('Label.textContent = Locale.get(', $source);
        self::assertStringContainsString('List.outerHTML,', $source);
        self::assertStringNotContainsString(
            "value=\"' + archiveFiles[i].name + '\"",
            $source
        );
    }

    public function testAttackFilenameRemainsTextAndAttributeData(): void
    {
        $Document = new DOMDocument('1.0', 'UTF-8');
        $List = $Document->createElement('ul');
        $Input = $Document->createElement('input');
        $Label = $Document->createElement('label');

        $Input->setAttribute('value', self::ATTACK_FILENAME);
        $Label->textContent = self::ATTACK_FILENAME . ' extracting';
        $List->appendChild($Input);
        $List->appendChild($Label);
        $Document->appendChild($List);

        $html = (string)$Document->saveHTML($List);

        self::assertStringNotContainsString('<img', $html);
        self::assertStringNotContainsString('<script', $html);
        self::assertSame(0, $List->getElementsByTagName('img')->length);
        self::assertSame(0, $List->getElementsByTagName('script')->length);

        $RenderedInput = $List->getElementsByTagName('input')->item(0);
        self::assertInstanceOf(DOMElement::class, $RenderedInput);
        self::assertSame(self::ATTACK_FILENAME, $RenderedInput->getAttribute('value'));
        self::assertSame(self::ATTACK_FILENAME . ' extracting', $Label->textContent);
    }
}
