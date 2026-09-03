<?php

declare(strict_types=1);

namespace QUI;

use DOMDocument;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function strpos;
use function substr;

final class PackageUploadPreviewSecurityTest extends TestCase
{
    private const ATTACK_NAME = 'attacker/package" autofocus onfocus="alert(1)';
    private const ATTACK_TITLE = '<img src=x onerror="alert(2)">';
    private const ATTACK_VERSION = '1.0"><script>alert(3)</script>';

    public function testPackageMetadataIsWrittenThroughNonParsingDomApis(): void
    {
        $source = (string)file_get_contents(
            dirname(__DIR__, 3) . '/bin/QUI/controls/packages/upload/Upload.js'
        );
        $methodStart = strpos($source, '$notInstalledPackagesFound: function');

        self::assertIsInt($methodStart);

        $methodEnd = strpos($source, '$install: function', $methodStart);

        self::assertIsInt($methodEnd);

        $method = substr($source, $methodStart, $methodEnd - $methodStart);

        self::assertStringContainsString("Checkbox.name = packages[i].name;", $method);
        self::assertStringContainsString("Checkbox.dataset.version = version;", $method);
        self::assertStringContainsString("PackageTitle.textContent = title;", $method);
        self::assertStringContainsString("PackageEntry = document.createElement('label');", $method);
        self::assertStringContainsString(
            "Container.querySelectorAll('[data-name=\"package\"]:checked')",
            $method
        );
        self::assertStringNotContainsString("html: '<input type=\"checkbox\"", $method);
        self::assertStringNotContainsString("innerHTML", $method);
    }

    public function testAttackMetadataRemainsTextAndAttributeData(): void
    {
        $Document = new DOMDocument('1.0', 'UTF-8');
        $Entry = $Document->createElement('label');
        $Checkbox = $Document->createElement('input');
        $PackageTitle = $Document->createElement('span');
        $title = self::ATTACK_TITLE . ' (' . self::ATTACK_VERSION . ')';

        $Checkbox->setAttribute('name', self::ATTACK_NAME);
        $Checkbox->setAttribute('data-version', self::ATTACK_VERSION);
        $PackageTitle->textContent = $title;
        $Entry->appendChild($Checkbox);
        $Entry->appendChild($PackageTitle);

        self::assertSame(self::ATTACK_NAME, $Checkbox->getAttribute('name'));
        self::assertSame(self::ATTACK_VERSION, $Checkbox->getAttribute('data-version'));
        self::assertSame($title, $PackageTitle->textContent);
        self::assertSame(0, $Entry->getElementsByTagName('img')->length);
        self::assertSame(0, $Entry->getElementsByTagName('script')->length);
    }
}
