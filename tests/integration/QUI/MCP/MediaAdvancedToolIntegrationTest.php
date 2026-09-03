<?php

declare(strict_types=1);

namespace QUI\MCP;

use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\MCP\Project\Media\ActivateMedia;
use QUI\MCP\Project\Media\CreateImageVariant;
use QUI\MCP\Project\Media\GetMediaEffects;
use QUI\MCP\Project\Media\GetMediaFolderPreview;
use QUI\MCP\Project\Media\UpdateMediaEffects;
use QUI\MCP\Project\Media\UpdateMediaFolderPreview;
use QUI\MCP\Project\Media\UpdateMediaOrder;
use QUI\MCP\Project\Media\UploadMedia;
use QUI\Permissions\Permission;
use QUI\Projects\Project;
use QUI\Projects\ProjectIntegrationTestCase;
use QUI\Projects\ProjectTestHelper;
use ReflectionProperty;
use Throwable;

class MediaAdvancedToolIntegrationTest extends ProjectIntegrationTestCase
{
    private const PNG_BASE64 =
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    public function testImageEffectsVariantsOrderAndFolderPreviewLifecycle(): void
    {
        self::skipIfDatabaseOrSuperUserIsUnavailable();
        $Project = self::getTestProject();
        $projectName = $Project->getName();
        $folderId = null;
        $imageIds = [];

        self::runAsRootUser(function () use ($Project, $projectName, &$folderId, &$imageIds): void {
            try {
                $folderId = ProjectTestHelper::runAsSystemUser(
                    static fn(): int => $Project->getMedia()->firstChild()
                        ->createFolder('mcp-media-advanced-' . uniqid())
                        ->getId()
                );
                $firstUpload = self::invokeTool(new UploadMedia(), [
                    $projectName,
                    $folderId,
                    'first-' . uniqid() . '.png',
                    self::PNG_BASE64
                ]);
                $secondUpload = self::invokeTool(new UploadMedia(), [
                    $projectName,
                    $folderId,
                    'second-' . uniqid() . '.png',
                    self::PNG_BASE64
                ]);
                $firstId = (int)$firstUpload['file']['id'];
                $secondId = (int)$secondUpload['file']['id'];
                $imageIds = [$firstId, $secondId];
                self::invokeTool(new ActivateMedia(), [$projectName, $firstId]);
                self::invokeTool(new ActivateMedia(), [$projectName, $secondId]);

                $effects = self::invokeTool(new UpdateMediaEffects(), [
                    $projectName,
                    $folderId,
                    [
                        'blur' => 4,
                        'brightness' => -5,
                        'contrast' => 10,
                        'greyscale' => true,
                        'watermark' => ''
                    ],
                    true
                ]);
                self::assertTrue($effects['updated']);
                self::assertTrue($effects['recursive']);
                self::assertContains($firstId, $effects['updatedChildren']);
                self::assertContains($secondId, $effects['updatedChildren']);
                self::assertSame([], $effects['errors']);

                $firstEffects = self::invokeTool(new GetMediaEffects(), [$projectName, $firstId]);
                self::assertSame(4, $firstEffects['effects']['blur']);
                self::assertSame(-5, $firstEffects['effects']['brightness']);
                self::assertSame(10, $firstEffects['effects']['contrast']);
                self::assertSame(1, $firstEffects['effects']['greyscale']);
                self::assertSame('', $firstEffects['effects']['watermark']);

                $variant = self::invokeTool(new CreateImageVariant(), [
                    $projectName,
                    $firstId,
                    1,
                    1
                ]);
                self::assertTrue($variant['created']);
                self::assertSame(1, $variant['variant']['width']);
                self::assertSame(1, $variant['variant']['height']);
                self::assertGreaterThan(0, $variant['variant']['sizeBytes']);
                self::assertNotSame('', $variant['variant']['url']);

                $ordered = self::invokeTool(new UpdateMediaOrder(), [
                    $projectName,
                    $folderId,
                    [$secondId, $firstId]
                ]);
                self::assertTrue($ordered['updated']);
                self::assertSame([$secondId, $firstId], array_slice($ordered['orderedIds'], 0, 2));

                $preview = self::invokeTool(new GetMediaFolderPreview(), [$projectName, $folderId]);
                self::assertSame($secondId, $preview['preview']['id']);

                $updatedPreview = self::invokeTool(new UpdateMediaFolderPreview(), [
                    $projectName,
                    $folderId,
                    $firstId
                ]);
                self::assertTrue($updatedPreview['updated']);
                self::assertSame($firstId, $updatedPreview['preview']['id']);

                $preview = self::invokeTool(new GetMediaFolderPreview(), [$projectName, $folderId]);
                self::assertSame($firstId, $preview['preview']['id']);
            } finally {
                if ($folderId !== null) {
                    self::cleanupFolder($Project, $folderId, $imageIds);
                }
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
     * @param array<int, int> $imageIds
     */
    private static function cleanupFolder(Project $Project, int $folderId, array $imageIds): void
    {
        ProjectTestHelper::runAsSystemUser(static function () use ($Project, $folderId, $imageIds): void {
            $Media = $Project->getMedia();

            foreach ($imageIds as $imageId) {
                try {
                    $Image = $Media->get($imageId);

                    if (!$Image->isDeleted()) {
                        $Image->delete();
                    }

                    $Image->destroy();
                } catch (Throwable) {
                }
            }

            try {
                $Folder = $Media->get($folderId);

                if (!$Folder->isDeleted()) {
                    $Folder->delete();
                }
            } catch (Throwable) {
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
            return $Callback();
        } finally {
            $SessionProperty->setValue($Users, $PreviousSessionUser);
            $PermissionProperty->setValue(null, $PreviousPermissionUser);
            $RequestUserProperty->setValue(null, $PreviousRequestUser);
        }
    }
}
