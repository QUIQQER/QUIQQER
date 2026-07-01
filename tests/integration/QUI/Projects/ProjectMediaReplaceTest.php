<?php

namespace QUI\Projects;

class ProjectMediaReplaceTest extends ProjectIntegrationTestCase
{
    public function testImageCanBeReplacedWithZeroMaxUploadSizeConfig(): void
    {
        $Project = self::getTestProject();
        $Media = $Project->getMedia();
        $Root = $Media->firstChild();
        $folderName = 'phpunit-replace-parent-' . uniqid();
        $sourceFile = sys_get_temp_dir() . '/quiqqer-phpunit-replace-source-' . uniqid() . '.png';
        $replacementFile = sys_get_temp_dir() . '/quiqqer-phpunit-replace-target-' . uniqid() . '.png';
        $originalConfig = $Project->getConfig();
        $fileId = null;
        $folderId = null;

        self::createPng($sourceFile);
        self::createPng($replacementFile);

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

            self::setProjectConfig($Project, array_merge($originalConfig, [
                'media_maxUploadSize' => 0
            ]));

            $Replaced = ProjectTestHelper::runAsSystemUser(
                static function () use ($Media, $fileId, $replacementFile) {
                    return $Media->replace($fileId, $replacementFile);
                }
            );

            $this->assertSame($fileId, $Replaced->getId());
            $this->assertSame('image', $Replaced->getAttribute('type'));
            $this->assertSame(1, (int)$Replaced->getAttribute('image_width'));
            $this->assertSame(1, (int)$Replaced->getAttribute('image_height'));
        } finally {
            self::setProjectConfig($Project, $originalConfig);

            if ($fileId) {
                ProjectTestHelper::runAsSystemUser(static function () use ($Media, $fileId): void {
                    $File = $Media->get($fileId);
                    $File->delete();
                    $File->destroy();
                });
            }

            if ($folderId) {
                ProjectTestHelper::runAsSystemUser(static function () use ($Media, $folderId): void {
                    $Folder = $Media->get($folderId);
                    $Folder->delete();
                    $Folder->destroy();
                });
            }

            if (file_exists($sourceFile)) {
                unlink($sourceFile);
            }

            if (file_exists($replacementFile)) {
                unlink($replacementFile);
            }
        }
    }

    private static function setProjectConfig(Project $Project, array $config): void
    {
        $Reflection = new \ReflectionClass($Project);
        $Property = $Reflection->getProperty('config');
        $Property->setValue($Project, $config);
    }

    private static function createPng(string $file): void
    {
        $Image = imagecreatetruecolor(1, 1);
        $Color = imagecolorallocate($Image, 255, 0, 0);
        imagefilledrectangle($Image, 0, 0, 1, 1, $Color);
        imagepng($Image, $file);
        imagedestroy($Image);
    }
}
