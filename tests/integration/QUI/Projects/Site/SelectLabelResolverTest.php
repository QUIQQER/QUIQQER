<?php

namespace QUI\Projects\Site;

use QUI;
use QUI\Cache\LongTermCache;
use QUI\Projects\ProjectIntegrationTestCase;
use QUI\Projects\ProjectTestHelper;

class SelectLabelResolverTest extends ProjectIntegrationTestCase
{
    public function testResolveReturnsSiteLabel(): void
    {
        [$siteId, $siteTitle] = $this->createSite();
        $siteValue = (string)$siteId;
        $result = $this->resolve([$siteValue]);

        self::assertSame([
            'value' => $siteValue,
            'kind' => 'site',
            'title' => $siteTitle,
            'icon' => 'fa fa-file-o'
        ], $result[$siteValue]);
    }

    public function testResolveReturnsChildrenLabel(): void
    {
        [$siteId, $siteTitle] = $this->createSite();
        $value = 'p' . $siteId;
        $result = $this->resolve([$value]);

        self::assertSame([
            'value' => $value,
            'kind' => 'children',
            'title' => $siteTitle,
            'icon' => 'fa fa-sitemap'
        ], $result[$value]);
    }

    public function testResolveReturnsTypeFallbackIcon(): void
    {
        $result = SelectLabelResolver::resolve(self::getTestProject(), ['standard']);

        self::assertSame([
            'value' => 'standard',
            'kind' => 'type',
            'title' => QUI::getLocale()->get('quiqqer/core', 'site.type.standard'),
            'icon' => 'fa fa-puzzle-piece'
        ], $result['standard']);
    }

    public function testResolveReturnsConfiguredTypeIcon(): void
    {
        $package = 'phpunit/select-label-resolver-' . uniqid();
        $packageDirectory = OPT_DIR . $package;
        $type = $package . ':types/custom';
        $cache = 'quiqqer/packages/xml-data/' . $type;

        mkdir($packageDirectory, 0777, true);
        file_put_contents(
            $packageDirectory . '/site.xml',
            '<?xml version="1.0"?><site><types><type type="types/custom" icon="fa fa-flask"/></types></site>'
        );
        LongTermCache::clear($cache);

        try {
            $result = SelectLabelResolver::resolve(self::getTestProject(), [$type]);

            self::assertSame([
                'value' => $type,
                'kind' => 'type',
                'title' => $type,
                'icon' => 'fa fa-flask'
            ], $result[$type]);
        } finally {
            LongTermCache::clear($cache);
            unlink($packageDirectory . '/site.xml');
            rmdir($packageDirectory);

            $vendorDirectory = dirname($packageDirectory);

            if (count(scandir($vendorDirectory)) === 2) {
                rmdir($vendorDirectory);
            }
        }
    }

    public function testResolveReturnsWildcardLabel(): void
    {
        $value = 'quiqqer/core:%';
        $result = SelectLabelResolver::resolve(self::getTestProject(), [$value]);

        self::assertSame([
            'value' => $value,
            'kind' => 'typeWildcard',
            'title' => QUI::getLocale()->get('quiqqer/core', 'package.title'),
            'icon' => 'fa fa-layer-group'
        ], $result[$value]);
    }

    public function testResolveKeepsValidEntryWhenSiteIsMissing(): void
    {
        [$siteId, $siteTitle] = $this->createSite();
        $siteValue = (string)$siteId;
        $missingSiteValue = '999999999';
        $result = $this->resolve([$missingSiteValue, $siteValue]);

        self::assertSame([
            $missingSiteValue => [
                'value' => $missingSiteValue,
                'kind' => 'site',
                'title' => '',
                'icon' => 'fa fa-file-o'
            ],
            $siteValue => [
                'value' => $siteValue,
                'kind' => 'site',
                'title' => $siteTitle,
                'icon' => 'fa fa-file-o'
            ]
        ], $result);
    }

    public function testResolveIgnoresDuplicateAndInvalidSelectors(): void
    {
        $result = SelectLabelResolver::resolve(
            self::getTestProject(),
            ['standard', 'standard', '', null, []]
        );

        self::assertSame(['standard'], array_keys($result));
    }

    public function testResolveReturnsEmptyArrayForEmptySelectors(): void
    {
        self::assertSame([], SelectLabelResolver::resolve(self::getTestProject(), []));
    }

    public function testResolveEncodedReturnsEmptyArrayForInvalidJson(): void
    {
        self::assertSame([], SelectLabelResolver::resolveEncoded(self::getTestProject(), 'invalid-json'));
    }

    public function testResolveEncodedReturnsDecodedEntries(): void
    {
        $result = SelectLabelResolver::resolveEncoded(self::getTestProject(), json_encode(['standard']));

        self::assertArrayHasKey('standard', $result);
    }

    /**
     * @return array{int, string}
     */
    private function createSite(): array
    {
        $Project = self::getTestProject();
        $siteTitle = 'PHPUnit Select Label ' . uniqid();
        $siteId = ProjectTestHelper::runAsSystemUser(static function () use ($Project, $siteTitle): int {
            return $Project->firstChild()->getEdit()->createChild([
                'name' => 'phpunit-select-label-' . uniqid(),
                'title' => $siteTitle
            ]);
        });

        return [$siteId, $siteTitle];
    }

    /**
     * @param array<array-key, mixed> $selectors
     * @return array<string, array{value: string, kind: string, title: string, icon: string}>
     */
    private function resolve(array $selectors): array
    {
        return ProjectTestHelper::runAsSystemUser(
            static fn(): array => SelectLabelResolver::resolve(self::getTestProject(), $selectors)
        );
    }
}
