<?php

namespace QUI\Projects;

class ProjectTitleLocaleDbalTest extends ProjectIntegrationTestCase
{
    public function testProjectTitleIsSavedToEditLocaleOnly(): void
    {
        $Project = self::getTestProject();
        $before = $Project->getTitleLocaleData();

        self::assertArrayHasKey('id', $before);

        ProjectTestHelper::runAsSystemUser(static function () use ($Project): void {
            $Project->setTitleLocaleData([
                'de' => 'NerdSpot – Grüße für Neugierige'
            ]);
        });

        $after = $Project->getTitleLocaleData();

        self::assertSame($before['id'], $after['id']);
        self::assertSame($before['de'], $after['de']);
        self::assertSame('NerdSpot – Grüße für Neugierige', $after['de_edit']);
    }
}
