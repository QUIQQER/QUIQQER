<?php

namespace QUI\Projects;

use QUI;

class ProjectFixtureTest extends ProjectIntegrationTestCase
{
    public function testProjectFixtureCreatesReusableProject(): void
    {
        $Project = self::getTestProject();
        $projectName = self::getTestProjectName();

        $this->assertStringStartsWith('phpunit', $projectName);
        $this->assertSame($projectName, $Project->getName());
        $this->assertContains('de', $Project->getLanguages());
        $this->assertTrue(
            QUI::getSchemaManager()->tablesExist([
                QUI::getDBTableName($projectName . '_de_sites'),
                QUI::getDBTableName($projectName . '_de_sites_relations'),
                QUI::getDBTableName($projectName . '_media'),
                QUI::getDBTableName($projectName . '_media_relations')
            ])
        );
    }
}
