<?php

declare(strict_types=1);

namespace QUI\Projects;

use DOMDocument;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function strpos;
use function substr;

final class ProjectSettingsSecurityTest extends TestCase
{
    private const ATTACK_TITLE = '<img src=x onerror="alert(1)"><script>alert(2)</script>';

    public function testLocalizedProjectTitleIsWrittenThroughTextContent(): void
    {
        $source = (string)file_get_contents(
            dirname(__DIR__, 4) . '/bin/QUI/controls/projects/project/Settings.js'
        );
        $methodStart = strpos($source, 'refreshData: function');

        self::assertIsInt($methodStart);

        $methodEnd = strpos($source, 'save: function', $methodStart);

        self::assertIsInt($methodEnd);

        $method = substr($source, $methodStart, $methodEnd - $methodStart);

        self::assertStringContainsString(
            "self.\$Title.textContent = self.getAttribute('title');",
            $method
        );
        self::assertStringNotContainsString("self.\$Title.set('html'", $method);
    }

    public function testAttackTitleRemainsText(): void
    {
        $Document = new DOMDocument('1.0', 'UTF-8');
        $Title = $Document->createElement('h2');
        $Title->textContent = self::ATTACK_TITLE;

        self::assertSame(self::ATTACK_TITLE, $Title->textContent);
        self::assertSame(0, $Title->getElementsByTagName('img')->length);
        self::assertSame(0, $Title->getElementsByTagName('script')->length);
    }
}
