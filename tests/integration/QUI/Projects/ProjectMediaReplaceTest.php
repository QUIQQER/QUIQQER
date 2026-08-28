<?php

namespace QUI\Projects;

use DOMDocument;
use DOMElement;
use DOMXPath;
use QUI;

use function iterator_to_array;
use function str_starts_with;

class ProjectMediaReplaceTest extends ProjectIntegrationTestCase
{
    public function testSvgReplacementIsSanitizedBeforeExistingFileIsRemoved(): void
    {
        $Project = self::getTestProject();
        $Media = $Project->getMedia();
        $Root = $Media->firstChild();
        $folderName = 'phpunit-replace-svg-parent-' . uniqid();
        $sourceFile = sys_get_temp_dir() . '/quiqqer-phpunit-replace-svg-source-' . uniqid() . '.png';
        $replacementFile = sys_get_temp_dir() . '/quiqqer-phpunit-replace-svg-target-' . uniqid() . '.svg';
        $invalidFile = sys_get_temp_dir() . '/quiqqer-phpunit-replace-svg-invalid-' . uniqid() . '.svg';
        $fileId = null;
        $folderId = null;

        self::createPng($sourceFile);
        file_put_contents(
            $replacementFile,
            '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="9" onload="alert(1)">'
            . '<script>alert(document.domain)</script>'
            . '<foreignObject><iframe src="https://attacker.invalid/"/></foreignObject>'
            . '<rect width="12" height="9" onclick="alert(2)"/>'
            . '</svg>'
        );
        file_put_contents($invalidFile, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script>');

        try {
            $folderId = ProjectTestHelper::runAsSystemUser(static function () use ($Root, $folderName): int {
                return $Root->createFolder($folderName)->getId();
            });

            $fileId = ProjectTestHelper::runAsSystemUser(
                static function () use ($Media, $folderId, $sourceFile): int {
                    return $Media->get($folderId)->uploadFile($sourceFile)->getId();
                }
            );

            $Original = $Media->get($fileId);
            $originalPath = $Original->getFullPath();
            $originalContent = file_get_contents($originalPath);

            try {
                ProjectTestHelper::runAsSystemUser(
                    static fn () => $Media->replace($fileId, $invalidFile)
                );
                self::fail('Malformed SVG replacement was accepted.');
            } catch (QUI\Exception $Exception) {
                self::assertSame(Media\ErrorCodes::FILE_IMAGE_CORRUPT, $Exception->getCode());
            }

            self::assertFileExists($originalPath);
            self::assertSame($originalContent, file_get_contents($originalPath));

            $Replaced = ProjectTestHelper::runAsSystemUser(
                static fn () => $Media->replace($fileId, $replacementFile)
            );
            $storedSvg = file_get_contents($Replaced->getFullPath());

            self::assertIsString($storedSvg);
            self::assertSame('image/svg+xml', $Replaced->getAttribute('mime_type'));
            self::assertSame(12, (int)$Replaced->getAttribute('image_width'));
            self::assertSame(9, (int)$Replaced->getAttribute('image_height'));

            $Document = new DOMDocument();
            self::assertTrue($Document->loadXML($storedSvg, LIBXML_NONET));
            $XPath = new DOMXPath($Document);
            self::assertSame(0, $XPath->query('//*[local-name()="script" or local-name()="foreignObject"]')->length);

            foreach ($XPath->query('//*') as $Element) {
                if (!$Element instanceof DOMElement) {
                    continue;
                }

                foreach (iterator_to_array($Element->attributes) as $Attribute) {
                    self::assertFalse(str_starts_with(strtolower($Attribute->nodeName), 'on'));
                    self::assertStringNotContainsString('attacker.invalid', (string)$Attribute->nodeValue);
                }
            }
        } finally {
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

            foreach ([$sourceFile, $replacementFile, $invalidFile] as $temporaryFile) {
                if (file_exists($temporaryFile)) {
                    unlink($temporaryFile);
                }
            }
        }
    }

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

    public function testMediaReplaceBeginReceivesPreviousMediaDataBeforeFileIsDeleted(): void
    {
        $Project = self::getTestProject();
        $Media = $Project->getMedia();
        $Root = $Media->firstChild();
        $folderName = 'phpunit-replace-begin-parent-' . uniqid();
        $sourceFile = sys_get_temp_dir() . '/quiqqer-phpunit-replace-begin-source-' . uniqid() . '.png';
        $replacementFile = sys_get_temp_dir() . '/quiqqer-phpunit-replace-begin-target-' . uniqid() . '.png';
        $fileId = null;
        $folderId = null;
        $eventCalls = 0;
        $eventPreviousData = null;
        $previousFileExistedDuringEvent = false;

        self::createPng($sourceFile);
        self::createPng($replacementFile);

        $listener = static function (
            Media $EventMedia,
            int $eventFileId,
            array $previousData
        ) use (
            $Media,
            &$fileId,
            &$eventCalls,
            &$eventPreviousData,
            &$previousFileExistedDuringEvent
        ): void {
            if ($eventFileId !== $fileId || $EventMedia !== $Media) {
                return;
            }

            $eventCalls++;
            $eventPreviousData = $previousData;
            $previousFileExistedDuringEvent = file_exists($EventMedia->getFullPath() . $previousData['file']);
        };

        QUI::getEvents()->addEvent('onMediaReplaceBegin', $listener);

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

            $uploadedFile = $Media->get($fileId);
            $previousFile = $uploadedFile->getAttribute('file');

            ProjectTestHelper::runAsSystemUser(
                static function () use ($Media, $fileId, $replacementFile): void {
                    $Media->replace($fileId, $replacementFile);
                }
            );

            $this->assertSame(1, $eventCalls);
            $this->assertIsArray($eventPreviousData);
            $this->assertSame($fileId, (int)$eventPreviousData['id']);
            $this->assertSame($previousFile, $eventPreviousData['file']);
            $this->assertSame('image/png', $eventPreviousData['mime_type']);
            $this->assertTrue($previousFileExistedDuringEvent);
        } finally {
            QUI::getEvents()->removeEvent('onMediaReplaceBegin', $listener);

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
