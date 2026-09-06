<?php

namespace QUI\Projects;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use QUI;
use QUI\Projects\Site\Edit;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class SiteUrlAfterMoveTest extends ProjectIntegrationTestCase
{
    public static function sorting(): array
    {
        return [['manual'], ['name']];
    }

    #[DataProvider('sorting')]
    public function testLocationEndpointUpdatesMovedSiteAndDescendant(string $sorting): void
    {
        if (!defined('ADMIN')) {
            define('ADMIN', 1);
        }

        $Project = self::getTestProject();
        $previousAjax = QUI::$Ajax;
        $previousRewrite = QUI::$Rewrite;
        $previousCacheConfig = QUI\Cache\Manager::getConfig();

        try {
            QUI\Cache\Manager::$Config = clone $previousCacheConfig;
            QUI\Cache\Manager::$Config->set('general', 'nocache', 0);
            QUI::$Ajax = new QUI\Ajax();
            require dirname(__DIR__, 4) . '/admin/ajax/site/getUrl.php';
            $getUrl = QUI\Ajax::getRegisteredCallables()['ajax_site_getUrl']['callable'];

            ProjectTestHelper::runAsSystemUser(function () use ($Project, $getUrl, $sorting): void {
                $Root = $Project->firstChild()->getEdit();
                $id = $Root->createChild(['name' => 'move-source']);
                $Source = new Edit($Project, $id);
                $childId = $Source->createChild(['name' => 'move-child']);
                $targetId = $Root->createChild(['name' => 'move-target']);
                $Target = new Edit($Project, $targetId);
                $Target->setAttribute('order_type', $sorting);
                $Target->save();
                $project = json_encode($Project->toArray());

                $before = $getUrl($project, $id);
                $childBefore = $getUrl($project, $childId);
                self::assertNotEmpty(QUI\Cache\Manager::get(Site::getLinkCachePath(
                    $Project->getName(),
                    $Project->getLang(),
                    $childId
                )));

                $Source->move($targetId);

                // URL refresh runs in a subsequent Ajax request with a new Output cache.
                QUI::$Rewrite = new QUI\Rewrite();
                $after = $getUrl($project, $id);
                $childAfter = $getUrl($project, $childId);

                self::assertSame(['url', 'parentid'], array_keys($after));
                self::assertSame($targetId, $after['parentid']);
                self::assertSame($id, $childAfter['parentid']);
                self::assertSame(
                    str_replace('/move-source', '/move-target/move-source', $before['url']),
                    $after['url']
                );
                self::assertSame(
                    str_replace('/move-source', '/move-target/move-source', $childBefore['url']),
                    $childAfter['url']
                );
            });
        } finally {
            QUI::$Ajax = $previousAjax;
            QUI::$Rewrite = $previousRewrite;
            QUI\Cache\Manager::$Config = $previousCacheConfig;
            ProjectTestHelper::cleanup();
        }
    }
}
