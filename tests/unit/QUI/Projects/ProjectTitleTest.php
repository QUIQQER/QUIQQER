<?php

declare(strict_types=1);

namespace QUI\Projects;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Locale;
use ReflectionClass;
use ReflectionProperty;

class ProjectTitleTest extends TestCase
{
    public function testEmptyLocaleTitleFallsBackToProjectName(): void
    {
        $Locale = $this->createMock(Locale::class);
        $Locale->method('getCurrent')->willReturn('en');
        $Locale->expects(self::once())
            ->method('exists')
            ->with('project/phpunit_title_fallback', 'title')
            ->willReturn(true);
        $Locale->expects(self::once())
            ->method('get')
            ->with('project/phpunit_title_fallback', 'title')
            ->willReturn('');

        $ProjectReflection = new ReflectionClass(Project::class);
        /** @var Project $Project */
        $Project = $ProjectReflection->newInstanceWithoutConstructor();
        $NameProperty = new ReflectionProperty(Project::class, 'name');
        $NameProperty->setValue($Project, 'phpunit_title_fallback');
        $PreviousLocale = QUI::$Locale;
        QUI::$Locale = $Locale;

        try {
            self::assertSame('phpunit_title_fallback', $Project->getTitle());
        } finally {
            QUI::$Locale = $PreviousLocale;
        }
    }
}
