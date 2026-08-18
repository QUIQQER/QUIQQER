<?php

declare(strict_types=1);

namespace QUITests\Template;

use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
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
        self::assertSame('2026-08-12 10:00:00', $JsonLd->get('datePublished'));
        self::assertSame('2026-08-12 11:00:00', $JsonLd->get('dateModified'));

        $website = $JsonLd->get('isPartOf');
        self::assertSame('https://example.com/#website', $website['@id']);

        $organization = $JsonLd->get('publisher');
        self::assertSame('https://example.com/#organization', $organization['@id']);

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

    public function testTemplateOmitsUnchangedOrInvalidModificationDates(): void
    {
        $Template = new AccessibleTemplate();
        $Template->initializeJsonLd($this->createSite(
            creationDate: '2026-08-12 10:00:00',
            editDate: '2026-08-12 10:00:00'
        ));

        self::assertNull($Template->getJsonLd()->get('dateModified'));

        $Template = new AccessibleTemplate();
        $Template->initializeJsonLd($this->createSite(
            creationDate: '2026-08-12 10:00:00',
            editDate: 'not-a-date'
        ));

        self::assertNull($Template->getJsonLd()->get('dateModified'));
    }

    public function testInternalPageReferencesSiteNodesAndAddsBreadcrumbs(): void
    {
        $Template = new AccessibleTemplate();
        $Template->initializeJsonLd($this->createSite(2));
        $JsonLd = $Template->getJsonLd();

        self::assertFalse($JsonLd->hasJsonLdNode('website'));
        self::assertFalse($JsonLd->hasJsonLdNode('organization'));
        self::assertSame(
            ['@id' => 'https://example.com/#website'],
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

    public function testPathLanguageReferencesDomainWebsiteWithoutRepeatingSiteNodes(): void
    {
        $Template = new AccessibleTemplate();
        $Template->initializeJsonLd($this->createSite(1, 'https://example.com/de/', 'de'));
        $JsonLd = $Template->getJsonLd();

        self::assertSame(
            ['@id' => 'https://example.com/#website'],
            $JsonLd->get('isPartOf')
        );
        self::assertSame(
            ['@id' => 'https://example.com/#organization'],
            $JsonLd->get('publisher')
        );
        self::assertFalse($JsonLd->hasJsonLdNode('website'));
        self::assertFalse($JsonLd->hasJsonLdNode('organization'));
    }

    public function testRootLanguageOnDedicatedDomainProvidesSiteNodes(): void
    {
        $Template = new AccessibleTemplate();
        $Template->initializeJsonLd($this->createSite(1, 'https://de.example.com/'));
        $JsonLd = $Template->getJsonLd();

        self::assertSame(
            'https://de.example.com/#website',
            $JsonLd->getJsonLdNode('website')['@id']
        );
        self::assertSame(
            'https://de.example.com/#organization',
            $JsonLd->getJsonLdNode('organization')['@id']
        );
    }

    public function testHtmlEntitiesAreFullyDecodedInTextAndUrls(): void
    {
        $Template = new AccessibleTemplate();
        $Template->initializeJsonLd($this->createSite(
            2,
            'https://example.com/de/',
            'de',
            'de/Blog/Kr&uuml;melmonster-Tag',
            'NerdSpot &mdash; Stories for Curious Minds'
        ));

        $schema = $Template->getJsonLd()->getJsonLdSchema();

        self::assertStringContainsString('NerdSpot — Stories for Curious Minds', $schema);
        self::assertStringContainsString('https://example.com/de/Blog/Krümelmonster-Tag', $schema);
        self::assertStringNotContainsString('&mdash;', $schema);
        self::assertStringNotContainsString('&uuml;', $schema);
        self::assertStringNotContainsString('&amp;', $schema);
    }

    #[DataProvider('jsonLdEntityProvider')]
    public function testJsonLdEntityVariantsAreDecoded(string $encoded, string $decoded): void
    {
        $Template = new AccessibleTemplate();
        $Template->initializeJsonLd($this->createSite(
            2,
            'https://example.com/de/',
            'de',
            'de/Blog/' . $encoded,
            $encoded
        ));

        $document = $this->decodeJsonLdSchema($Template->getJsonLd()->getJsonLdSchema());

        self::assertSame($decoded, $document['@graph'][0]['name']);
        self::assertSame(
            'https://example.com/de/Blog/' . $decoded,
            $document['@graph'][0]['url']
        );
        self::assertSame(
            'https://example.com/de/Blog/' . $decoded . '#webpage',
            $document['@graph'][0]['@id']
        );
    }

    public function testNestedPageBlogPostingAndBreadcrumbEntitiesAreDecoded(): void
    {
        $Template = new AccessibleTemplate();
        $Template->initializeJsonLd($this->createSite(
            2,
            'https://example.com/de/',
            'de',
            'de/Blog/Kr&uuml;melmonster-Tag',
            'NerdSpot &mdash; Stories f&uuml;r Neugierige'
        ));

        $JsonLd = $Template->getJsonLd();
        $JsonLd->setJsonLdNode('blogPosting', [
            '@type' => 'BlogPosting',
            '@id' => 'https://example.com/de/Blog/Kr&uuml;melmonster-Tag#article',
            'url' => 'https://example.com/de/Blog/Kr&uuml;melmonster-Tag',
            'headline' => 'Kr&uuml;melmonster &mdash; Gr&uuml;&szlig;e',
            'mainEntityOfPage' => [
                '@id' => 'https://example.com/de/Blog/Kr&uuml;melmonster-Tag#webpage'
            ]
        ]);
        $JsonLd->setJsonLdNode('breadcrumb', [
            '@type' => 'BreadcrumbList',
            '@id' => 'https://example.com/de/Blog/Kr&uuml;melmonster-Tag#breadcrumb',
            'itemListElement' => [[
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Kr&uuml;melmonster &mdash; Tag',
                'item' => 'https://example.com/de/Blog/Kr&uuml;melmonster-Tag'
            ]]
        ]);

        $document = $this->decodeJsonLdSchema($JsonLd->getJsonLdSchema());
        $page = $document['@graph'][0];
        $breadcrumb = $document['@graph'][1];
        $blogPosting = $document['@graph'][2];

        self::assertSame('NerdSpot — Stories für Neugierige', $page['name']);
        self::assertSame('https://example.com/de/Blog/Krümelmonster-Tag', $page['url']);
        self::assertSame(
            'https://example.com/de/Blog/Krümelmonster-Tag#webpage',
            $page['@id']
        );
        self::assertSame('Krümelmonster — Tag', $breadcrumb['itemListElement'][0]['name']);
        self::assertSame(
            'https://example.com/de/Blog/Krümelmonster-Tag',
            $breadcrumb['itemListElement'][0]['item']
        );
        self::assertSame('Krümelmonster — Grüße', $blogPosting['headline']);
        self::assertSame(
            'https://example.com/de/Blog/Krümelmonster-Tag#article',
            $blogPosting['@id']
        );
        self::assertSame(
            'https://example.com/de/Blog/Krümelmonster-Tag',
            $blogPosting['url']
        );
        self::assertSame(
            ['@id' => 'https://example.com/de/Blog/Krümelmonster-Tag#webpage'],
            $blogPosting['mainEntityOfPage']
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function jsonLdEntityProvider(): iterable
    {
        yield 'unicode' => ['Krümelmonster — Grüße', 'Krümelmonster — Grüße'];
        yield 'named entities' => ['Kr&uuml;melmonster &mdash; Gr&uuml;&szlig;e', 'Krümelmonster — Grüße'];
        yield 'decimal entities' => ['Kr&#252;melmonster &#8212; Gr&#252;&#223;e', 'Krümelmonster — Grüße'];
        yield 'hexadecimal entities' => ['Kr&#xFC;melmonster &#x2014; Gr&#xFC;&#xDF;e', 'Krümelmonster — Grüße'];
    }

    public function testEmptyWebsiteTitleFallsBackToProjectName(): void
    {
        $Template = new AccessibleTemplate();
        $Template->initializeJsonLd($this->createSite(
            projectTitle: '',
            projectName: 'nerdspott'
        ));

        self::assertSame(
            'nerdspott',
            $Template->getJsonLd()->getJsonLdNode('website')['name']
        );
    }

    /**
     * @return array<string, mixed>
     * @throws JsonException
     */
    private function decodeJsonLdSchema(string $schema): array
    {
        $json = preg_replace(
            '#^<script type="application/ld\+json">|</script>$#',
            '',
            $schema
        );

        return json_decode((string)$json, true, 512, JSON_THROW_ON_ERROR);
    }

    private function createSite(
        int $siteId = 1,
        string $websiteBaseUrl = 'https://example.com/',
        string $languagePath = '',
        string $rewrittenUrl = 'de/example',
        string $siteTitle = 'Example &mdash; page',
        string $projectTitle = 'Example &amp; website',
        string $projectName = 'example',
        string $creationDate = '2026-08-12 10:00:00',
        string $editDate = '2026-08-12 11:00:00'
    ): Site {
        $Project = $this->createMock(Project::class);
        $Project->method('getVHostBaseUrl')->willReturn($websiteBaseUrl);
        $Project->method('getVHostPath')->willReturn($languagePath);
        $Project->method('getVHost')->willReturn('https://example.com');
        $Project->method('getTitle')->willReturn($projectTitle);
        $Project->method('getName')->willReturn($projectName);
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
        $Site->method('getUrlRewritten')->willReturn($rewrittenUrl);
        $Site->method('getAttribute')->willReturnCallback(static function (string $name) use (
            $siteTitle,
            $creationDate,
            $editDate
        ): string {
            return match ($name) {
                'title' => $siteTitle,
                'meta.description' => 'Description from meta settings',
                'short' => 'Fallback description',
                'release_from', 'c_date' => $creationDate,
                'e_date' => $editDate,
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
