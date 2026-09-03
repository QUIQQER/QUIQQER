<?php

declare(strict_types=1);

namespace QUI\MCP;

use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\Interfaces\Users\User as UserInterface;
use QUI\MCP\Project\Media\CopyMedia;
use QUI\MCP\Project\Media\DeleteMedia;
use QUI\MCP\Project\Media\DownloadMedia;
use QUI\MCP\Project\Media\DownloadMediaFolder;
use QUI\MCP\Project\Media\GetMediaFolderSize;
use QUI\MCP\Project\Media\MoveMedia;
use QUI\MCP\Project\Media\RenameMedia;
use QUI\MCP\Project\Media\ReplaceMedia;
use QUI\MCP\Project\Media\UpdateMediaVisibility;
use QUI\MCP\Project\Media\UploadMedia;
use QUI\MCP\Project\Sites\DeleteSite;
use QUI\MCP\Project\Trash\ClearMediaTrash;
use QUI\MCP\Project\Trash\ClearSiteTrash;
use QUI\MCP\Project\Trash\DestroyMedia;
use QUI\MCP\Project\Trash\DestroySites;
use QUI\MCP\Project\Trash\ListMediaTrash;
use QUI\MCP\Project\Trash\ListSiteTrash;
use QUI\MCP\Project\Trash\RestoreMedia;
use QUI\MCP\Project\Trash\RestoreSites;
use QUI\Permissions\Permission;
use QUI\Projects\Project;
use QUI\Projects\ProjectIntegrationTestCase;
use QUI\Projects\ProjectTestHelper;
use QUI\Projects\Site\Edit;
use ReflectionProperty;
use Throwable;
use ZipArchive;

class MediaTrashToolIntegrationTest extends ProjectIntegrationTestCase
{
    public function testMediaOperationsAndTrashLifecycle(): void
    {
        self::skipIfDatabaseOrSuperUserIsUnavailable();
        $Project = self::getTestProject();
        $projectName = $Project->getName();
        $folderIds = [];
        $rootFileIds = [];

        self::runAsRootUser(function () use (
            $Project,
            $projectName,
            &$folderIds,
            &$rootFileIds
        ): void {
            try {
                [$sourceFolderId, $targetFolderId] = ProjectTestHelper::runAsSystemUser(
                    static function () use ($Project): array {
                        $Root = $Project->getMedia()->firstChild();

                        return [
                            $Root->createFolder('mcp-media-source-' . uniqid())->getId(),
                            $Root->createFolder('mcp-media-target-' . uniqid())->getId()
                        ];
                    }
                );
                $folderIds = [$sourceFolderId, $targetFolderId];
                $originalName = 'original-' . uniqid() . '.txt';
                $renamedName = 'renamed-mcp-' . uniqid();
                $copiedName = 'copied-mcp-' . uniqid();

                $uploaded = self::invokeTool(new UploadMedia(), [
                    $projectName,
                    $sourceFolderId,
                    $originalName,
                    base64_encode('original MCP content')
                ]);
                $sourceFileId = (int)$uploaded['file']['id'];

                $download = self::invokeTool(new DownloadMedia(), [$projectName, $sourceFileId]);
                self::assertSame(
                    'original MCP content',
                    base64_decode($download['download']['contentBase64'], true)
                );

                $hidden = self::invokeTool(new UpdateMediaVisibility(), [
                    $projectName,
                    [$sourceFileId],
                    false
                ]);
                self::assertTrue((bool)$hidden['items'][0]['attributes']['hidden']);

                $visible = self::invokeTool(new UpdateMediaVisibility(), [
                    $projectName,
                    [$sourceFileId],
                    true
                ]);
                self::assertFalse((bool)$visible['items'][0]['attributes']['hidden']);

                $renamed = self::invokeTool(new RenameMedia(), [
                    $projectName,
                    $sourceFileId,
                    $renamedName . '.txt'
                ]);
                self::assertSame($renamedName, $renamed['item']['name']);

                $copy = self::invokeTool(new CopyMedia(), [
                    $projectName,
                    [$sourceFileId],
                    $targetFolderId
                ]);
                self::assertSame(1, $copy['copied']);
                self::assertSame([], $copy['errors']);
                $copyId = (int)$copy['items'][0]['item']['id'];

                self::invokeTool(new RenameMedia(), [$projectName, $copyId, $copiedName]);

                $move = self::invokeTool(new MoveMedia(), [
                    $projectName,
                    [$sourceFileId],
                    $targetFolderId
                ]);
                self::assertSame(1, $move['moved']);
                self::assertSame([], $move['errors']);
                self::assertSame($targetFolderId, $Project->getMedia()->get($sourceFileId)->getParentId());

                $replacementContent = 'replacement MCP content';
                $replaced = self::invokeTool(new ReplaceMedia(), [
                    $projectName,
                    $sourceFileId,
                    'replacement.txt',
                    base64_encode($replacementContent)
                ]);
                self::assertTrue($replaced['replaced']);

                $replacementDownload = self::invokeTool(new DownloadMedia(), [
                    $projectName,
                    $sourceFileId
                ]);
                self::assertSame(
                    $replacementContent,
                    base64_decode($replacementDownload['download']['contentBase64'], true)
                );

                $folderSize = self::invokeTool(new GetMediaFolderSize(), [
                    $projectName,
                    $targetFolderId,
                    true
                ]);
                self::assertTrue($folderSize['sizeKnown']);
                self::assertGreaterThan(0, $folderSize['sizeBytes']);

                if (class_exists(ZipArchive::class)) {
                    $folderDownload = self::invokeTool(new DownloadMediaFolder(), [
                        $projectName,
                        $targetFolderId
                    ]);
                    $zipContent = base64_decode($folderDownload['download']['contentBase64'], true);
                    self::assertIsString($zipContent);
                    self::assertStringStartsWith('PK', $zipContent);
                }

                self::invokeTool(new DeleteMedia(), [$projectName, $copyId]);
                $trash = self::invokeTool(new ListMediaTrash(), [$projectName]);
                self::assertContains($copyId, array_map('intval', array_column($trash['items'], 'id')));

                $restored = self::invokeTool(new RestoreMedia(), [
                    $projectName,
                    [$copyId],
                    1
                ]);
                self::assertSame(1, $restored['restored']);
                $restoredId = (int)$restored['items'][0]['item']['id'];
                $rootFileIds[] = $restoredId;

                self::invokeTool(new DeleteMedia(), [$projectName, $restoredId]);
                $destroyed = self::invokeTool(new DestroyMedia(), [
                    $projectName,
                    [$restoredId],
                    true
                ]);
                self::assertSame([$restoredId], $destroyed['ids']);
                $rootFileIds = [];

                self::invokeTool(new DeleteMedia(), [$projectName, $sourceFileId]);
                self::invokeTool(new DestroyMedia(), [$projectName, [$sourceFileId], true]);

                $clearFile = self::invokeTool(new UploadMedia(), [
                    $projectName,
                    $targetFolderId,
                    'clear-me-' . uniqid() . '.txt',
                    base64_encode('clear me')
                ]);
                $clearFileId = (int)$clearFile['file']['id'];
                self::invokeTool(new DeleteMedia(), [$projectName, $clearFileId]);
                $cleared = self::invokeTool(new ClearMediaTrash(), [$projectName, true]);
                self::assertTrue($cleared['cleared']);
                self::assertSame(0, $cleared['remaining']);
            } finally {
                self::cleanupMediaFixtures($Project, $folderIds, $rootFileIds);
            }
        });
    }

    public function testSiteTrashRestoreDestroyAndClearLifecycle(): void
    {
        self::skipIfDatabaseOrSuperUserIsUnavailable();
        $Project = self::getTestProject();
        $projectName = $Project->getName();
        $lang = $Project->getLang();
        $siteIds = [];

        self::runAsRootUser(function () use ($Project, $projectName, $lang, &$siteIds): void {
            try {
                [$restoreId, $clearId] = ProjectTestHelper::runAsSystemUser(
                    static function () use ($Project): array {
                        $Root = $Project->firstChild()->getEdit();

                        return [
                            $Root->createChild([
                                'name' => 'mcp-trash-restore-' . uniqid(),
                                'title' => 'MCP trash restore'
                            ]),
                            $Root->createChild([
                                'name' => 'mcp-trash-clear-' . uniqid(),
                                'title' => 'MCP trash clear'
                            ])
                        ];
                    }
                );
                $siteIds = [$restoreId, $clearId];

                self::invokeTool(new DeleteSite(), [$projectName, $restoreId, $lang]);
                $trash = self::invokeTool(new ListSiteTrash(), [$projectName, $lang]);
                self::assertContains(
                    $restoreId,
                    array_map('intval', array_column($trash['items'], 'id')),
                    json_encode($trash) ?: ''
                );

                $restored = self::invokeTool(new RestoreSites(), [
                    $projectName,
                    [$restoreId],
                    1,
                    $lang
                ]);
                self::assertSame(1, $restored['restored']);
                self::assertFalse($restored['sites'][0]['active']);

                self::invokeTool(new DeleteSite(), [$projectName, $restoreId, $lang]);
                $destroyed = self::invokeTool(new DestroySites(), [
                    $projectName,
                    [$restoreId],
                    true,
                    $lang
                ]);
                self::assertSame([$restoreId], $destroyed['ids']);

                self::invokeTool(new DeleteSite(), [$projectName, $clearId, $lang]);
                $cleared = self::invokeTool(new ClearSiteTrash(), [$projectName, true, $lang]);
                self::assertTrue($cleared['cleared']);
                self::assertGreaterThanOrEqual(1, $cleared['destroyed']);
                $siteIds = [];
            } finally {
                self::cleanupSiteFixtures($Project, $siteIds);
            }
        });
    }

    /**
     * @param array<int, mixed> $arguments
     * @return array<string, mixed>
     */
    private static function invokeTool(ToolInterface $Tool, array $arguments): array
    {
        $Builder = new Builder();
        $Tool->register($Builder);
        $tools = (new ReflectionProperty(Builder::class, 'tools'))->getValue($Builder);
        $Handler = $tools[0]['handler'] ?? $tools[0]['callback'] ?? null;

        self::assertIsCallable($Handler);
        $result = $Handler(...$arguments);

        self::assertIsArray($result);

        return $result;
    }

    /**
     * @param array<int, int> $folderIds
     * @param array<int, int> $rootFileIds
     */
    private static function cleanupMediaFixtures(Project $Project, array $folderIds, array $rootFileIds): void
    {
        ProjectTestHelper::runAsSystemUser(static function () use ($Project, $folderIds, $rootFileIds): void {
            $Media = $Project->getMedia();

            foreach ($rootFileIds as $id) {
                try {
                    $Item = $Media->get($id);

                    if (!$Item->isDeleted()) {
                        $Item->delete();
                    }

                    $Item->destroy();
                } catch (Throwable) {
                }
            }

            foreach (array_reverse($folderIds) as $id) {
                try {
                    $Folder = $Media->get($id);

                    if (!$Folder->isDeleted()) {
                        $Folder->delete();
                    }
                } catch (Throwable) {
                }
            }
        });
    }

    /**
     * @param array<int, int> $siteIds
     */
    private static function cleanupSiteFixtures(Project $Project, array $siteIds): void
    {
        ProjectTestHelper::runAsSystemUser(static function () use ($Project, $siteIds): void {
            foreach (array_reverse($siteIds) as $id) {
                try {
                    $Site = new Edit($Project, $id);

                    if ((int)$Site->getAttribute('deleted') !== 1) {
                        $Site->delete();
                    }

                    $Site->refresh();
                    $Site->destroy();
                } catch (Throwable) {
                }
            }
        });
    }

    private static function skipIfDatabaseOrSuperUserIsUnavailable(): void
    {
        try {
            QUI::getDataBaseConnection()->executeQuery('SELECT 1')->free();
            $RootUser = QUI::getUsers()->get(QUI::conf('globals', 'rootuser'));
        } catch (Throwable $Exception) {
            self::markTestSkipped('QUIQQER user database is unavailable: ' . $Exception->getMessage());
        }

        if (!$RootUser->isSU()) {
            self::markTestSkipped('QUIQQER database has no usable super-user fixture.');
        }
    }

    private static function runAsRootUser(callable $Callback): mixed
    {
        $Users = QUI::getUsers();
        $RootUser = $Users->get(QUI::conf('globals', 'rootuser'));
        $SessionProperty = new ReflectionProperty($Users, 'Session');
        $PermissionProperty = new ReflectionProperty(Permission::class, 'User');
        $RequestUserProperty = new ReflectionProperty(Server::class, 'RequestUser');
        $PreviousSessionUser = $SessionProperty->getValue($Users);
        $PreviousPermissionUser = $PermissionProperty->getValue();
        $PreviousRequestUser = $RequestUserProperty->getValue();

        $SessionProperty->setValue($Users, $RootUser);
        $PermissionProperty->setValue(null, $RootUser);
        $RequestUserProperty->setValue(null, $RootUser);

        try {
            return $Callback($RootUser);
        } finally {
            $SessionProperty->setValue($Users, $PreviousSessionUser);
            $PermissionProperty->setValue(null, $PreviousPermissionUser);
            $RequestUserProperty->setValue(null, $PreviousRequestUser);
        }
    }
}
