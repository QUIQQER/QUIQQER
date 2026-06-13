<?php

namespace QUI\Projects;

use QUI\Projects\Media\Folder;

class ProjectMediaDbalTest extends ProjectIntegrationTestCase
{
    public function testMediaFolderCanBeCreatedAndLoadedFromTestProject(): void
    {
        $Project = self::getTestProject();
        $Media = $Project->getMedia();
        $Root = $Media->firstChild();
        $folderName = 'phpunit-folder-' . uniqid();

        $Folder = ProjectTestHelper::runAsSystemUser(static function () use ($Root, $folderName): Folder {
            return $Root->createFolder($folderName);
        });

        $ReloadedFolder = $Media->get($Folder->getId());

        $this->assertInstanceOf(Folder::class, $ReloadedFolder);
        $this->assertGreaterThan(1, $Folder->getId());
        $this->assertSame($folderName, $ReloadedFolder->getAttribute('name'));
        $this->assertSame('folder', $ReloadedFolder->getAttribute('type'));
        $this->assertSame($folderName . '/', $ReloadedFolder->getAttribute('file'));
    }
}
