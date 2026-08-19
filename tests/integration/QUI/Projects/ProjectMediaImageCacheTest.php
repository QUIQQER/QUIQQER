<?php

namespace QUI\Projects;

use QUI\Projects\Media\Image;
use ReflectionClass;

class ProjectMediaImageCacheTest extends ProjectIntegrationTestCase
{
    public function testSmallCacheImageUsesExactTargetDimensions(): void
    {
        $Project = self::getTestProject();
        $Media = $Project->getMedia();
        $Root = $Media->firstChild();
        $folderName = 'phpunit-image-cache-' . uniqid();
        $sourceFile = sys_get_temp_dir() . '/quiqqer-phpunit-image-cache-' . uniqid() . '.png';
        $originalConfig = $Project->getConfig();
        $fileId = null;
        $folderId = null;

        self::createPng($sourceFile, 380, 80);

        try {
            self::setProjectConfig($Project, array_merge($originalConfig, [
                'media_imageCacheSizeRounding' => 1,
                'media_imageCacheExactSizeThreshold' => 100
            ]));

            $folderId = ProjectTestHelper::runAsSystemUser(static function () use ($Root, $folderName): int {
                return $Root->createFolder($folderName)->getId();
            });

            $fileId = ProjectTestHelper::runAsSystemUser(
                static function () use ($Media, $folderId, $sourceFile): int {
                    $Image = $Media->get($folderId)->uploadFile($sourceFile);
                    $Image->activate();

                    return $Image->getId();
                }
            );

            $Image = $Media->get($fileId);
            $this->assertInstanceOf(Image::class, $Image);

            $cachePath = ProjectTestHelper::runAsSystemUser(
                static fn (): string => $Image->getSizeCachePath(false, 60)
            );
            $createdCachePath = ProjectTestHelper::runAsSystemUser(
                static fn (): false | string => $Image->createSizeCache(false, 60)
            );

            $this->assertMatchesRegularExpression('/__285x60\.png$/', $cachePath);
            $this->assertSame($cachePath, $createdCachePath);
            $this->assertFileExists($cachePath);

            $imageSize = getimagesize($cachePath);
            $this->assertIsArray($imageSize);
            $this->assertSame(285, $imageSize[0]);
            $this->assertSame(60, $imageSize[1]);

            self::setProjectConfig($Project, array_merge($originalConfig, [
                'media_imageCacheSizeRounding' => 1,
                'media_imageCacheExactSizeThreshold' => 0
            ]));

            $roundedPath = ProjectTestHelper::runAsSystemUser(
                static fn (): string => $Image->getSizeCachePath(false, 60)
            );

            $this->assertMatchesRegularExpression('/__288x61\.png$/', $roundedPath);
        } finally {
            self::setProjectConfig($Project, $originalConfig);
            self::deleteMediaItems($Media, $fileId, $folderId);

            if (file_exists($sourceFile)) {
                unlink($sourceFile);
            }
        }
    }

    public function testCacheSizeRoundingCanBeConfigured(): void
    {
        $Project = self::getTestProject();
        $Media = $Project->getMedia();
        $Root = $Media->firstChild();
        $folderName = 'phpunit-image-cache-config-' . uniqid();
        $sourceFile = sys_get_temp_dir() . '/quiqqer-phpunit-image-cache-config-' . uniqid() . '.png';
        $originalConfig = $Project->getConfig();
        $fileId = null;
        $folderId = null;

        self::createPng($sourceFile, 1600, 900);

        try {
            $folderId = ProjectTestHelper::runAsSystemUser(static function () use ($Root, $folderName): int {
                return $Root->createFolder($folderName)->getId();
            });

            $fileId = ProjectTestHelper::runAsSystemUser(
                static function () use ($Media, $folderId, $sourceFile): int {
                    $Image = $Media->get($folderId)->uploadFile($sourceFile);
                    $Image->activate();

                    return $Image->getId();
                }
            );

            $Image = $Media->get($fileId);
            $this->assertInstanceOf(Image::class, $Image);

            self::setProjectConfig($Project, array_merge($originalConfig, [
                'media_imageCacheSizeRounding' => 1,
                'media_imageCacheExactSizeThreshold' => 100
            ]));

            $roundedPath = ProjectTestHelper::runAsSystemUser(
                static fn (): string => $Image->getSizeCachePath(333)
            );

            self::setProjectConfig($Project, array_merge($originalConfig, [
                'media_imageCacheSizeRounding' => 0,
                'media_imageCacheExactSizeThreshold' => 100
            ]));

            $exactPath = ProjectTestHelper::runAsSystemUser(
                static fn (): string => $Image->getSizeCachePath(333)
            );

            $this->assertMatchesRegularExpression('/__336x189\.png$/', $roundedPath);
            $this->assertMatchesRegularExpression('/__333x187\.png$/', $exactPath);
        } finally {
            self::setProjectConfig($Project, $originalConfig);
            self::deleteMediaItems($Media, $fileId, $folderId);

            if (file_exists($sourceFile)) {
                unlink($sourceFile);
            }
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function setProjectConfig(Project $Project, array $config): void
    {
        $Reflection = new ReflectionClass($Project);
        $Property = $Reflection->getProperty('config');
        $Property->setValue($Project, $config);
    }

    private static function deleteMediaItems(
        Media $Media,
        ?int $fileId,
        ?int $folderId
    ): void {
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
    }

    private static function createPng(string $file, int $width, int $height): void
    {
        $Image = imagecreatetruecolor($width, $height);
        $Color = imagecolorallocate($Image, 255, 0, 0);
        imagefilledrectangle($Image, 0, 0, $width - 1, $height - 1, $Color);
        imagepng($Image, $file);
        imagedestroy($Image);
    }
}
