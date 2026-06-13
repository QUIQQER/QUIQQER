<?php

namespace QUI\Projects;

use QUI;
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

    public function testMediaFolderChildrenCanBeCountedLimitedAndLoadedByType(): void
    {
        $Project = self::getTestProject();
        $Media = $Project->getMedia();
        $Root = $Media->firstChild();
        $firstFolderName = 'phpunit-child-a-' . uniqid();
        $secondFolderName = 'phpunit-child-b-' . uniqid();

        ProjectTestHelper::runAsSystemUser(static function () use ($Root, $firstFolderName, $secondFolderName): void {
            $Root->createFolder($firstFolderName);
            $Root->createFolder($secondFolderName);
        });

        $count = $Root->getChildrenIds([
            'count' => true
        ]);
        $ids = $Root->getChildrenIds([
            'order' => 'name ASC',
            'limit' => '0,1'
        ]);
        $Folder = $Media->get($ids[0]);

        $this->assertGreaterThanOrEqual(2, $count);
        $this->assertInstanceOf(Folder::class, $Folder);
        $this->assertStringStartsWith('phpunit-child-a-', $Folder->getAttribute('name'));
        $this->assertGreaterThanOrEqual(2, $Root->getFolders(['active' => 0, 'count' => true]));
        $this->assertSame([], $Root->getFiles(['where' => ['file' => 'not-existing.txt']]));
        $this->assertSame([], $Root->getImages(['where' => ['file' => 'not-existing.png']]));
    }

    public function testMediaFolderCanBeRenamedCopiedAndFoundByNameAndPath(): void
    {
        $Project = self::getTestProject();
        $Media = $Project->getMedia();
        $Root = $Media->firstChild();
        $folderName = 'phpunit-media-lookup-' . uniqid();
        $renamedName = $folderName . '-renamed';
        $copyTargetName = 'phpunit-media-copy-target-' . uniqid();

        [$folderId, $copyTargetId] = ProjectTestHelper::runAsSystemUser(
            static function () use ($Root, $folderName, $copyTargetName): array {
                $Folder = $Root->createFolder($folderName);
                $CopyTarget = $Root->createFolder($copyTargetName);

                return [$Folder->getId(), $CopyTarget->getId()];
            }
        );

        ProjectTestHelper::runAsSystemUser(static function () use ($Media, $folderId, $renamedName): void {
            $Media->get($folderId)->rename($renamedName);
        });

        $Renamed = $Media->getChildByPath($renamedName . '/');
        $this->assertSame($folderId, $Renamed->getId());
        $this->assertTrue($Root->childWithNameExists($renamedName));
        $this->assertSame($folderId, $Root->getChildByName($renamedName)->getId());

        $copyId = ProjectTestHelper::runAsSystemUser(
            static function () use ($Media, $folderId, $copyTargetId): int {
                $Copy = $Media->get($folderId)->copyTo($Media->get($copyTargetId));

                return $Copy->getId();
            }
        );

        $Copy = $Media->get($copyId);
        $Connection = QUI::getDataBaseConnection();
        $Platform = $Connection->getDatabasePlatform();
        $storedParentId = $Connection->createQueryBuilder()
            ->select($Platform->quoteSingleIdentifier('parent'))
            ->from($Platform->quoteSingleIdentifier($Media->getTable('relations')))
            ->where($Platform->quoteSingleIdentifier('child') . ' = :childId')
            ->setParameter('childId', $copyId)
            ->executeQuery()
            ->fetchOne();

        $this->assertInstanceOf(Folder::class, $Copy);
        $this->assertSame($copyTargetId, (int)$storedParentId);
        $this->assertSame($renamedName, $Copy->getAttribute('name'));
    }

    public function testMediaFileCanBeUploadedRenamedActivatedDeletedAndDestroyed(): void
    {
        $Project = self::getTestProject();
        $Media = $Project->getMedia();
        $Root = $Media->firstChild();
        $folderName = 'phpunit-upload-parent-' . uniqid();
        $sourceFile = sys_get_temp_dir() . '/quiqqer-phpunit-upload-' . uniqid() . '.txt';
        file_put_contents($sourceFile, 'QUIQQER PHPUnit media upload');

        try {
            $folderId = ProjectTestHelper::runAsSystemUser(static function () use ($Root, $folderName): int {
                return $Root->createFolder($folderName)->getId();
            });

            $fileId = ProjectTestHelper::runAsSystemUser(
                static function () use ($Media, $folderId, $sourceFile): int {
                    $File = $Media->get($folderId)->uploadFile($sourceFile);

                    return $File->getId();
                }
            );

            $File = $Media->get($fileId);
            $this->assertSame($folderId, $File->getParentId());
            $this->assertSame('file', $File->getAttribute('type'));
            $this->assertSame('quiqqer-phpunit-upload-', substr($File->getAttribute('name'), 0, 23));
            $this->assertSame($fileId, $Media->getChildByPath($File->getAttribute('file'))->getId());

            ProjectTestHelper::runAsSystemUser(static function () use ($Media, $fileId): void {
                $File = $Media->get($fileId);
                $File->setTitle('PHPUnit Uploaded File');
                $File->setAlt('PHPUnit Uploaded Alt');
                $File->setDescription('PHPUnit Uploaded Description');
                $File->setAttribute('priority', 3);
                $File->save();
                $File->rename('phpunit-renamed-upload');
                $File->activate();
            });

            $File = $Media->get($fileId);
            $this->assertSame('PHPUnit Uploaded File', $File->getTitle());
            $this->assertSame('PHPUnit Uploaded Alt', $File->getAlt());
            $this->assertSame('PHPUnit Uploaded Description', $File->getShort());
            $this->assertSame(3, (int)$File->getAttribute('priority'));
            $this->assertSame('phpunit-renamed-upload', $File->getAttribute('name'));
            $this->assertTrue($File->isActive());
            $this->assertFileExists($File->getFullPath());

            ProjectTestHelper::runAsSystemUser(static function () use ($Media, $fileId): void {
                $File = $Media->get($fileId);
                $File->deactivate();
                $File->delete();
                $File->destroy();
            });

            $Connection = QUI::getDataBaseConnection();
            $Platform = $Connection->getDatabasePlatform();
            $exists = $Connection->createQueryBuilder()
                ->select('COUNT(' . $Platform->quoteSingleIdentifier('id') . ')')
                ->from($Platform->quoteSingleIdentifier($Media->getTable()))
                ->where($Platform->quoteSingleIdentifier('id') . ' = :mediaId')
                ->setParameter('mediaId', $fileId)
                ->executeQuery()
                ->fetchOne();

            $this->assertSame(0, (int)$exists);
        } finally {
            if (file_exists($sourceFile)) {
                unlink($sourceFile);
            }
        }
    }

    public function testMediaFolderLifecycleCanEditActivateDeactivateMoveAndDelete(): void
    {
        $Project = self::getTestProject();
        $Media = $Project->getMedia();
        $Root = $Media->firstChild();
        $parentAName = 'phpunit-media-parent-a-' . uniqid();
        $parentBName = 'phpunit-media-parent-b-' . uniqid();
        $childName = 'phpunit-media-child-' . uniqid();

        [$parentAId, $parentBId, $childId] = ProjectTestHelper::runAsSystemUser(
            static function () use ($Root, $parentAName, $parentBName, $childName): array {
                $ParentA = $Root->createFolder($parentAName);
                $ParentB = $Root->createFolder($parentBName);
                $Child = $ParentA->createFolder($childName);

                return [$ParentA->getId(), $ParentB->getId(), $Child->getId()];
            }
        );

        $Child = $Media->get($childId);
        $this->assertInstanceOf(Folder::class, $Child);
        $this->assertSame($parentAId, $Child->getParentId());

        ProjectTestHelper::runAsSystemUser(static function () use ($Media, $childId): void {
            $Child = $Media->get($childId);
            $Child->setTitle('PHPUnit Media Child Edited');
            $Child->setDescription('PHPUnit media edited short text');
            $Child->setAttribute('order', 'name ASC');
            $Child->setAttribute('priority', 7);
            $Child->save();
            $Child->activate();
        });

        $Child = $Media->get($childId);
        $this->assertSame('PHPUnit Media Child Edited', $Child->getTitle());
        $this->assertSame('PHPUnit media edited short text', $Child->getShort());
        $this->assertSame('name ASC', $Child->getAttribute('order'));
        $this->assertSame(7, (int)$Child->getAttribute('priority'));
        $this->assertTrue($Child->isActive());

        ProjectTestHelper::runAsSystemUser(static function () use ($Media, $childId, $parentBId): void {
            $Child = $Media->get($childId);
            $ParentB = $Media->get($parentBId);
            $Child->deactivate();
            $Child->moveTo($ParentB);
        });

        $Child = $Media->get($childId);
        $this->assertFalse($Child->isActive());
        $Connection = QUI::getDataBaseConnection();
        $Platform = $Connection->getDatabasePlatform();
        $storedParentId = $Connection->createQueryBuilder()
            ->select($Platform->quoteSingleIdentifier('parent'))
            ->from($Platform->quoteSingleIdentifier($Media->getTable('relations')))
            ->where($Platform->quoteSingleIdentifier('child') . ' = :childId')
            ->setParameter('childId', $childId)
            ->executeQuery()
            ->fetchOne();

        $this->assertSame($parentBId, (int)$storedParentId);

        $ParentB = $Media->get($parentBId);
        $this->assertContains($childId, $ParentB->getChildrenIds(['active' => 0]));

        ProjectTestHelper::runAsSystemUser(static function () use ($Media, $childId): void {
            $Media->get($childId)->delete();
        });

        $Connection = QUI::getDataBaseConnection();
        $Platform = $Connection->getDatabasePlatform();
        $exists = $Connection->createQueryBuilder()
            ->select('COUNT(' . $Platform->quoteSingleIdentifier('id') . ')')
            ->from($Platform->quoteSingleIdentifier($Media->getTable()))
            ->where($Platform->quoteSingleIdentifier('id') . ' = :mediaId')
            ->setParameter('mediaId', $childId)
            ->executeQuery()
            ->fetchOne();

        $this->assertSame(0, (int)$exists);
    }
}
