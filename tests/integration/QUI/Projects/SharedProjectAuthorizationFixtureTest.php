<?php

declare(strict_types=1);

namespace QUI\Projects;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Ajax;
use QUI\Permissions\Permission;
use QUI\System\Console\Session;
use ReflectionProperty;
use RuntimeException;

require_once __DIR__ . '/ProjectAuthorizationFixtureHarness.php';

final class SharedProjectAuthorizationFixtureTest extends TestCase
{
    public function testProjectCleanupRemovesTheTranslationGroupAsWellAsPublishedFiles(): void
    {
        $projectName = 'phpunit_locale_cleanup_' . bin2hex(random_bytes(5));
        $group = 'project/' . $projectName;

        try {
            QUI\Translator::add($group, 'title');
            QUI\Translator::publish($group);
            self::assertContains($group, QUI\Translator::getGroupList());
            self::assertNotEmpty(glob(VAR_DIR . 'locale/*/LC_MESSAGES/project_' . $projectName . '.ini.php'));

            QUI\System\TestCleanup::cleanupProject($projectName);

            self::assertNotContains($group, QUI\Translator::getGroupList());
            self::assertSame([], QUI\Translator::get($group, 'title'));
            self::assertSame([], glob(VAR_DIR . 'locale/*/LC_MESSAGES/project_' . $projectName . '.ini.php'));
        } finally {
            QUI\System\TestCleanup::cleanupProject($projectName);
        }
    }

    public function testRuntimeStateIsRestoredWhenTheTestThrows(): void
    {
        $PreviousAjax = QUI::$Ajax;
        $PreviousSession = QUI::$Session;
        $PermissionUser = new ReflectionProperty(Permission::class, 'User');
        $PreviousPermissionUser = $PermissionUser->getValue();
        $Callbacks = new ReflectionProperty(QUI::getErrorHandler(), 'shutDownCallbacks');
        $previousCallbacks = $Callbacks->getValue(QUI::getErrorHandler());
        $previousCallables = Ajax::getRegisteredCallables();
        $name = 'phpunit_fixture_' . bin2hex(random_bytes(5));

        try {
            $this->withFixture(static function () use ($name): void {
                QUI::$Ajax = new Ajax();
                QUI::$Session = new Session();
                Permission::setUser(QUI::getUsers()->getSystemUser());
                Ajax::registerFunction($name, static fn(): bool => true);
                throw new RuntimeException('fixture test failure');
            });
            self::fail('The fixture must propagate the original failure.');
        } catch (RuntimeException $Exception) {
            self::assertSame('fixture test failure', $Exception->getMessage());
        }

        self::assertSame($PreviousAjax, QUI::$Ajax);
        self::assertSame($PreviousSession, QUI::$Session);
        self::assertSame($PreviousPermissionUser, $PermissionUser->getValue());
        self::assertSame($previousCallbacks, $Callbacks->getValue(QUI::getErrorHandler()));
        self::assertSame($previousCallables, Ajax::getRegisteredCallables());
    }

    public function testDeletedFolderLeavesNoDescendantTrashOrAclAndPreservesExistingObjects(): void
    {
        $Project = ProjectTestHelper::getProject();
        $Media = $Project->getMedia();
        $System = QUI::getUsers()->getSystemUser();
        $Existing = ProjectTestHelper::runAsSystemUser(
            fn() => $Media->firstChild()->createFolder('fixture-existing-' . bin2hex(random_bytes(5)))
        );
        $temporaryFile = tempnam(sys_get_temp_dir(), 'quiqqer-fixture-');
        self::assertIsString($temporaryFile);
        file_put_contents($temporaryFile, 'shared project fixture regression');
        $folderId = null;
        $fileId = null;

        try {
            $this->withFixture(function () use ($Media, $System, $temporaryFile, &$folderId, &$fileId): void {
                ProjectTestHelper::runAsSystemUser(function () use (
                    $Media,
                    $System,
                    $temporaryFile,
                    &$folderId,
                    &$fileId
                ): void {
                    $Folder = $Media->firstChild()->createFolder('fixture-created-' . bin2hex(random_bytes(5)));
                    $folderId = $Folder->getId();
                    $File = $Folder->uploadFile($temporaryFile);
                    $fileId = $File->getId();
                    QUI::getPermissionManager()->setMediaPermissions($Folder, [
                        'quiqqer.projects.media.view' => [$System]
                    ], $System);
                    $Folder->delete();
                    self::assertFileExists($Media->getTrash()->getPath() . $fileId);
                });
            });

            self::assertFileDoesNotExist($Media->getTrash()->getPath() . $fileId);

            foreach ([$folderId, $fileId] as $id) {
                self::assertFalse(QUI::getDataBaseConnection()->createQueryBuilder()
                    ->select('id')
                    ->from(QUI\Utils\Doctrine::quoteIdentifier($Media->getTable()))
                    ->where('id = :id')
                    ->setParameter('id', $id)
                    ->executeQuery()
                    ->fetchOne());
                self::assertFalse(QUI::getDataBaseConnection()->createQueryBuilder()
                    ->select('id')
                    ->from(QUI\Utils\Doctrine::quoteIdentifier(QUI::getPermissionManager()::table() . '2media'))
                    ->where('project = :project')
                    ->andWhere('id = :id')
                    ->setParameter('project', $Project->getName())
                    ->setParameter('id', $id)
                    ->executeQuery()
                    ->fetchOne());
            }

            self::assertDirectoryExists($Existing->getFullPath());
            self::assertSame($Existing->getId(), $Media->get($Existing->getId())->getId());
        } finally {
            ProjectTestHelper::runAsSystemUser(static fn() => $Existing->delete());

            if (file_exists($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }

    private function withFixture(callable $Test): void
    {
        $Fixture = new ProjectAuthorizationFixtureHarness('fixture');

        try {
            $Fixture->open();
            $Test();
        } finally {
            $Fixture->close();
        }
    }
}
