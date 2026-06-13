<?php

namespace QUI\Projects;

class ProjectSiteDbalTest extends ProjectIntegrationTestCase
{
    public function testSiteChildCanBeCreatedAndLoadedFromTestProject(): void
    {
        $Project = self::getTestProject();
        $Root = $Project->firstChild()->getEdit();
        $siteName = 'phpunit-site-' . uniqid();
        $siteTitle = 'PHPUnit Site ' . uniqid();

        $siteId = ProjectTestHelper::runAsSystemUser(static function () use ($Root, $siteName, $siteTitle): int {
            return $Root->createChild([
                'name' => $siteName,
                'title' => $siteTitle,
                'short' => 'PHPUnit short text',
                'content' => '<p>PHPUnit content</p>'
            ]);
        });

        $Site = new Site\Edit($Project, $siteId);

        $this->assertGreaterThan(1, $siteId);
        $this->assertSame($siteName, $Site->getAttribute('name'));
        $this->assertSame($siteTitle, $Site->getAttribute('title'));
        $this->assertSame('PHPUnit short text', $Site->getAttribute('short'));
        $this->assertSame('<p>PHPUnit content</p>', $Site->getAttribute('content'));
    }
}
