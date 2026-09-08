<?php

namespace QUI\Projects;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use QUI;
use QUI\Projects\Site\Edit;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class SiteSelectionSearchEndpointTest extends ProjectIntegrationTestCase
{
    public function testSearchFindsNestedPagesByTitleNameAndIdAndOmitsDeletedPages(): void
    {
        // Match the administration endpoint: unpublished pages are selectable as well.
        if (!defined('ADMIN')) {
            define('ADMIN', 1);
        }

        $Project = self::getTestProject();
        $previousAjax = QUI::$Ajax;
        $created = [];

        try {
            ProjectTestHelper::runAsSystemUser(function () use ($Project, &$created): void {
                $suffix = bin2hex(random_bytes(6));
                $parentId = $Project->firstChild()->getEdit()->createChild(['name' => 'search-parent-' . $suffix]);
                $created[] = $parentId;
                $Parent = new Edit($Project, $parentId);
                $id = $Parent->createChild(['name' => 'nested-' . $suffix, 'title' => 'Find title ' . $suffix]);
                $created[] = $id;

                QUI::$Ajax = new QUI\Ajax();
                require dirname(__DIR__, 4) . '/admin/ajax/project/sites/searchForSelection.php';
                $search = QUI\Ajax::getRegisteredCallables()['ajax_project_sites_searchForSelection']['callable'];
                $project = json_encode($Project->toArray());

                foreach (['Find title ' . $suffix, 'nested-' . $suffix, (string)$id] as $term) {
                    $result = $search($project, $term);
                    self::assertContains($id, array_column($result['items'], 'id'), $term . ': ' . json_encode($result));
                }

                (new Edit($Project, $id))->delete();
                self::assertSame([], $search($project, 'nested-' . $suffix)['items']);
                self::assertNotContains($id, array_column($search($project, (string)$id)['items'], 'id'));
            });
        } finally {
            ProjectTestHelper::runAsSystemUser(function () use ($Project, $created): void {
                foreach (array_reverse($created) as $id) {
                    $Site = new Edit($Project, $id);
                    if (!$Site->getAttribute('deleted')) {
                        $Site->delete();
                    }
                    $Site->destroy();
                }
            });
            QUI::$Ajax = $previousAjax;
        }
    }
}
