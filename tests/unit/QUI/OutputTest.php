<?php

namespace QUI;

use PHPUnit\Framework\TestCase;
use QUI\Projects\Project;
use QUI\Utils\JsonLd;
use ReflectionMethod;

class OutputTest extends TestCase
{
    public function testPathLanguageIsPrefixedToSiteUrl(): void
    {
        $Project = $this->createMock(Project::class);
        $Project->method('getVHostPath')->willReturn('es');
        $Project->method('hasVHost')->willReturn(true);

        $prependProjectLanguagePath = new ReflectionMethod(
            Output::class,
            'prependProjectLanguagePath'
        );

        self::assertSame(
            'es/example',
            $prependProjectLanguagePath->invoke(
                new Output(),
                'example',
                $Project,
                ['www.example.eu' => []]
            )
        );
    }

    public function testRootLanguageDoesNotPrefixSiteUrl(): void
    {
        $Project = $this->createMock(Project::class);
        $Project->method('getVHostPath')->willReturn('');
        $Project->method('hasVHost')->willReturn(true);

        $prependProjectLanguagePath = new ReflectionMethod(
            Output::class,
            'prependProjectLanguagePath'
        );

        self::assertSame(
            'example',
            $prependProjectLanguagePath->invoke(
                new Output(),
                'example',
                $Project,
                ['www.example.eu' => []]
            )
        );
    }

    public function testUnassignedLanguageKeepsLegacyLanguagePrefix(): void
    {
        $Project = $this->createMock(Project::class);
        $Project->method('getVHostPath')->willReturn('');
        $Project->method('hasVHost')->willReturn(false);
        $Project->method('getLang')->willReturn('es');

        $prependProjectLanguagePath = new ReflectionMethod(
            Output::class,
            'prependProjectLanguagePath'
        );

        self::assertSame(
            'es/example',
            $prependProjectLanguagePath->invoke(
                new Output(),
                'example',
                $Project,
                ['www.example.eu' => []]
            )
        );
    }

    public function testParseWithAbsoluteUrlsConvertsImageSrcsetUrlsWithoutPicture(): void
    {
        $Output = new Output();
        $Output->setSetting('use-absolute-urls', true);

        $host = trim(HOST, '/');
        $html = '<img src="/media/cache/image.jpg" ' .
            'srcset="/media/cache/image-320.webp 320w, media/cache/image-640.webp 640w, ' .
            'https://cdn.example.com/image-960.webp 960w, //cdn.example.com/image-1280.webp 1280w" ' .
            'alt="Test">';

        $this->assertSame(
            '<img src="' . $host . '/media/cache/image.jpg" ' .
            'srcset="' . $host . '/media/cache/image-320.webp 320w, ' .
            $host . '/media/cache/image-640.webp 640w, ' .
            'https://cdn.example.com/image-960.webp 960w, ' .
            '//cdn.example.com/image-1280.webp 1280w" ' .
            'alt="Test">',
            $Output->parse($html)
        );
    }

    public function testParsePreservesUnicodeInsideJsonLd(): void
    {
        $JsonLd = new JsonLd();
        $JsonLd->set('type', 'WebPage');
        $JsonLd->set('name', 'Kr&uuml;melmonster — Grüße');
        $JsonLd->set('url', 'https://example.com/Kr&uuml;melmonster');

        $html = '<!doctype html><html><head>' . $JsonLd->getJsonLdSchema() . '</head>' .
            '<body><img src="/media/image.jpg" alt="Krümelmonster"></body></html>';

        $result = (new Output())->parse($html);

        self::assertStringContainsString('Krümelmonster — Grüße', $result);
        self::assertStringContainsString('https://example.com/Krümelmonster', $result);
        self::assertStringNotContainsString('Kr&uuml;melmonster', $result);
        self::assertStringNotContainsString('&mdash;', $result);
    }
}
