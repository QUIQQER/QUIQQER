<?php

declare(strict_types=1);

namespace QUI\MCP;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\MCP\Project\AbstractProjectLifecycleTool;
use ReflectionMethod;

class ProjectSiteAdministrationToolTest extends TestCase
{
    public function testProjectLanguagesAreNormalizedAndDeduplicated(): void
    {
        $availableLanguages = QUI::availableLanguages();

        if ($availableLanguages === []) {
            self::markTestSkipped('No installed language is available.');
        }

        $language = strtolower((string)$availableLanguages[0]);
        $Method = new ReflectionMethod(AbstractProjectLifecycleTool::class, 'normalizeLanguages');

        self::assertSame(
            [$language],
            $Method->invoke(null, strtoupper($language), [$language, strtoupper($language)])
        );
    }

    public function testProjectLanguagesRejectNonStringValues(): void
    {
        $availableLanguages = QUI::availableLanguages();

        if ($availableLanguages === []) {
            self::markTestSkipped('No installed language is available.');
        }

        $Method = new ReflectionMethod(AbstractProjectLifecycleTool::class, 'normalizeLanguages');

        $this->expectException(QUI\Exception::class);
        $Method->invoke(null, (string)$availableLanguages[0], [42]);
    }

    public function testProjectDeletionRequiresExplicitConfirmation(): void
    {
        $Method = new ReflectionMethod(AbstractProjectLifecycleTool::class, 'requireConfirmation');

        $this->expectException(QUI\Exception::class);
        $Method->invoke(null, false);
    }
}
