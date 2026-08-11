<?php

declare(strict_types=1);

namespace QUITests\Template;

use PHPUnit\Framework\TestCase;
use QUI\Interfaces\Projects\Site;
use QUI\Projects\Project;

require_once __DIR__ . '/AccessibleTemplate.php';

class JsonLdTest extends TestCase
{
    public function testTemplateProvidesGeneralSiteJsonLd(): void
    {
        $Template = new AccessibleTemplate();
        $Template->initializeJsonLd($this->createSite());
        $JsonLd = $Template->getJsonLd();

        self::assertSame('WebPage', $JsonLd->get('type'));
        self::assertSame('Example — page', $JsonLd->get('name'));
        self::assertSame('Description from meta settings', $JsonLd->get('description'));
        self::assertSame('de', $JsonLd->get('inLanguage'));

        $website = $JsonLd->get('isPartOf');
        self::assertSame('https://example.com/de/#website', $website['@id']);

        $organization = $JsonLd->get('publisher');
        self::assertSame('https://example.com/de/#organization', $organization['@id']);

        self::assertSame('WebSite', $JsonLd->getJsonLdNode('website')['@type']);
        self::assertSame('Example & website', $JsonLd->getJsonLdNode('website')['name']);
        self::assertSame('Organization', $JsonLd->getJsonLdNode('organization')['@type']);
        self::assertSame('Example Publisher', $JsonLd->getJsonLdNode('organization')['name']);
    }

    public function testSiteTypeCanOverrideTypeAndProperties(): void
    {
        $Template = new AccessibleTemplate();
        $Template->initializeJsonLd($this->createSite());
        $JsonLd = $Template->getJsonLd();

        $JsonLd->set('type', 'CollectionPage');
        $JsonLd->set('name', 'Custom collection');
        $JsonLd->set('breadcrumb', [
            '@type' => 'BreadcrumbList',
            'itemListElement' => []
        ]);

        self::assertSame('CollectionPage', $JsonLd->get('type'));
        self::assertSame('Custom collection', $JsonLd->get('name'));
        self::assertSame('BreadcrumbList', $JsonLd->get('breadcrumb')['@type']);
        self::assertStringContainsString('"@type":"CollectionPage"', $JsonLd->getJsonLdSchema());
    }

    public function testInternalPageReferencesSiteNodesAndAddsBreadcrumbs(): void
    {
        $Template = new AccessibleTemplate();
        $Template->initializeJsonLd($this->createSite(2));
        $JsonLd = $Template->getJsonLd();

        self::assertFalse($JsonLd->hasJsonLdNode('website'));
        self::assertFalse($JsonLd->hasJsonLdNode('organization'));
        self::assertSame(
            ['@id' => 'https://example.com/de/#website'],
            $JsonLd->get('isPartOf')
        );

        self::assertSame(
            ['@id' => 'https://example.com/de/example#breadcrumb'],
            $JsonLd->get('breadcrumb')
        );

        $breadcrumb = $JsonLd->getJsonLdNode('breadcrumb');

        self::assertSame('BreadcrumbList', $breadcrumb['@type']);
        self::assertSame(
            [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Homepage',
                    'item' => 'https://example.com/de/'
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Example — page',
                    'item' => 'https://example.com/de/example'
                ]
            ],
            $breadcrumb['itemListElement']
        );
        self::assertStringContainsString('"@graph"', $JsonLd->getJsonLdSchema());
    }

    private function createSite(int $siteId = 1): Site
    {
        $Project = $this->createMock(Project::class);
        $Project->method('getVHostBaseUrl')->willReturn('https://example.com/de/');
        $Project->method('getVHost')->willReturn('https://example.com');
        $Project->method('getTitle')->willReturn('Example &amp; website');
        $Project->method('getLang')->willReturn('de');
        $Project->method('getConfig')->willReturnCallback(static function (false | string $name): string {
            return match ($name) {
                'publisher' => 'Example Publisher',
                'publisher_url' => 'https://example.com/',
                'publisher_type' => 'organization',
                default => ''
            };
        });

        $Site = $this->createMock(\QUI\Projects\Site::class);
        $Site->method('getProject')->willReturn($Project);
        $Site->method('getId')->willReturn($siteId);
        $Site->method('getUrlRewritten')->willReturn('de/example');
        $Site->method('getAttribute')->willReturnCallback(static function (string $name): string {
            return match ($name) {
                'title' => 'Example &mdash; page',
                'meta.description' => 'Description from meta settings',
                'short' => 'Fallback description',
                default => ''
            };
        });

        if ($siteId === 1) {
            $Home = $Site;
            $Site->method('getParents')->willReturn([]);
        } else {
            $Home = $this->createMock(\QUI\Projects\Site::class);
            $Home->method('getProject')->willReturn($Project);
            $Home->method('getId')->willReturn(1);
            $Home->method('getUrlRewritten')->willReturn('de/');
            $Home->method('getAttribute')->willReturnCallback(static function (string $name): string {
                return $name === 'title' ? 'Homepage' : '';
            });
            $Site->method('getParents')->willReturn([$Home]);
        }

        $Project->method('firstChild')->willReturn($Home);

        return $Site;
    }
}
