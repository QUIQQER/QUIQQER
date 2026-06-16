<?php

namespace QUI\Projects;

class ProjectManagerDbalTest extends ProjectIntegrationTestCase
{
    public function testProjectFixtureIsAvailableThroughManagerAndProjectList(): void
    {
        $Project = self::getTestProject();
        $projectName = self::getTestProjectName();

        $this->assertTrue(Manager::existsProject($projectName));
        $this->assertContains($projectName, Manager::getProjects());

        $projects = Manager::getProjects(true);
        $projectNames = array_map(static function (Project $Project): string {
            return $Project->getName();
        }, $projects);

        $this->assertContains($projectName, $projectNames);
        $this->assertSame($Project->getName(), Manager::getProject($projectName)->getName());
        $this->assertSame($Project->getLang(), Manager::getProject($projectName, $Project->getLang())->getLang());
    }

    public function testProjectExposesCoreConfigurationTablesAndRoots(): void
    {
        $Project = self::getTestProject();
        $Media = $Project->getMedia();
        $RootSite = $Project->firstChild();
        $RootMedia = $Media->firstChild();

        $this->assertSame(self::getTestProjectName(), $Project->getName());
        $this->assertNotSame('', $Project->getLang());
        $this->assertContains($Project->getLang(), $Project->getLanguages());
        $this->assertNotSame('', $Project->getTitle());
        $this->assertSame(5, $Project->getConfig('sheets'));
        $this->assertSame(10, $Project->getConfig('archive'));
        $this->assertStringEndsWith('_sites', $Project->table());
        $this->assertStringEndsWith('_media', $Media->getTable());
        $this->assertStringEndsWith('_media_relations', $Media->getTable('relations'));
        $this->assertSame(1, $RootSite->getId());
        $this->assertSame(1, $RootMedia->getId());
        $this->assertSame($Project->getName(), $Media->getProject()->getName());
    }

    public function testProjectCanResolveRootAndMediaObjectsById(): void
    {
        $Project = self::getTestProject();
        $RootSite = $Project->firstChild();
        $RootMedia = $Project->getMedia()->firstChild();

        $this->assertSame($RootSite->getId(), $Project->get($RootSite->getId())->getId());
        $this->assertSame($RootMedia->getId(), $Project->getMedia()->get($RootMedia->getId())->getId());
        $this->assertSame([], $Project->getParentIds($RootSite->getId()));
        $this->assertSame(0, $Project->getParentId($RootSite->getId()));
    }
}
