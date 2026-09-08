<?php

declare(strict_types=1);

namespace QUI\Projects;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\AI\MCP\Server;
use QUI\Ajax;
use QUI\Permissions\Permission;
use QUI\Projects\Media\Folder;
use QUI\Projects\Site\Edit;
use ReflectionProperty;
use Throwable;

/**
 * Reuse the project schema while isolating each authorization test's runtime state and objects.
 * These fixtures must run sequentially against the database and filesystem.
 */
abstract class ProjectAuthorizationTestCase extends TestCase
{
    /** @var list<array{ReflectionProperty, ?object, mixed}> */
    private array $savedProperties = [];

    private ?Project $fixtureProject = null;
    private bool $fixtureReady = false;
    private int $lastMediaId = 0;
    private int $lastSiteId = 0;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            QUI::getDataBaseConnection()->executeQuery('SELECT 1')->free();
        } catch (Throwable $Exception) {
            self::markTestSkipped('QUIQQER database fixtures are unavailable: ' . $Exception->getMessage());
        }

        foreach (['Ajax', 'Session', 'Users', 'Rights', 'MessageHandler', 'Rewrite'] as $property) {
            $this->remember(QUI::class, $property);
        }

        $this->remember(Permission::class, 'User');
        $this->remember(Server::class, 'RequestUser');
        $this->remember(Media::class, 'mediaPermissions');
        $this->remember(Media::class, 'globalDisableMediaCacheCreation');

        foreach (['functions', 'callables', 'permissions'] as $property) {
            $this->remember(Ajax::class, $property);
        }

        $this->remember(QUI::getErrorHandler(), 'shutDownCallbacks');
        $this->remember(QUI::getMessagesHandler(), 'messages');
        $this->remember(QUI::getRewrite(), 'registerPaths');

        $Users = QUI::getUsers();
        $this->remember($Users, 'Session');
        $this->remember($Users, 'multipleCallPrevention');

        foreach (['users', 'usersUUIDs'] as $property) {
            $this->remember($Users, $property);
            (new ReflectionProperty($Users, $property))->setValue($Users, []);
        }

        $Permissions = QUI::getPermissionManager();

        foreach (['cache', 'dataCache', 'permissionsCache'] as $property) {
            $this->remember($Permissions, $property);

            if ($property !== 'cache') {
                (new ReflectionProperty($Permissions, $property))->setValue($Permissions, []);
            }
        }

        $this->fixtureProject = ProjectTestHelper::getProject();
        $Media = $this->fixtureProject->getMedia();
        $this->remember($this->fixtureProject, 'cache_files');

        foreach (['children', 'firstchild'] as $property) {
            $this->remember($this->fixtureProject, $property);
            (new ReflectionProperty($this->fixtureProject, $property))->setValue(
                $this->fixtureProject,
                $property === 'children' ? [] : null
            );
        }

        $this->remember($Media, 'children');
        (new ReflectionProperty($Media, 'children'))->setValue($Media, []);

        $this->lastMediaId = $this->lastId($Media->getTable());
        $this->lastSiteId = $this->lastId($this->fixtureProject->table());
        $this->fixtureReady = true;
    }

    protected function tearDown(): void
    {
        try {
            if ($this->fixtureReady) {
                ProjectTestHelper::runAsSystemUser(function (): void {
                    $this->cleanupRemainingMedia();
                    $this->cleanupRemainingSites();
                    $this->fixtureProject->clearCache();
                    $this->fixtureProject->getMedia()->clearCache();
                });
            }
        } finally {
            foreach (array_reverse($this->savedProperties) as [$Property, $Object, $value]) {
                $Property->setValue($Object, $value);
            }

            $this->savedProperties = [];
            $this->fixtureProject = null;
            $this->fixtureReady = false;
            parent::tearDown();
        }
    }

    private function remember(object|string $target, string $property): void
    {
        $Property = new ReflectionProperty($target, $property);
        $Object = is_object($target) ? $target : null;
        $this->savedProperties[] = [$Property, $Object, $Property->getValue($Object)];
    }

    private function lastId(string $table): int
    {
        return (int)QUI::getDataBaseConnection()->createQueryBuilder()
            ->select('MAX(id)')
            ->from(QUI\Utils\Doctrine::quoteIdentifier($table))
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Folder::delete() moves descendant files into the trash. Remove those files too,
     * including descendants whose names do not carry the test's prefix.
     */
    private function cleanupRemainingMedia(): void
    {
        $Media = $this->fixtureProject->getMedia();
        (new ReflectionProperty($Media, 'children'))->setValue($Media, []);

        $rows = QUI::getDataBaseConnection()->createQueryBuilder()
            ->select('*')
            ->from(QUI\Utils\Doctrine::quoteIdentifier($Media->getTable()))
            ->where('id > :lastId')
            ->setParameter('lastId', $this->lastMediaId)
            ->orderBy('id', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($rows as $row) {
            // Media::get() deliberately hides deleted files in frontend mode.
            $Item = $Media->parseResultToItem($row);

            if ($Item instanceof Folder) {
                continue;
            }

            if (!$Item->isDeleted()) {
                $Item->delete();
            }

            $Item->destroy();
        }

        foreach ($this->remainingIds($Media->getTable(), $this->lastMediaId) as $id) {
            $Media->get($id)->delete();
        }

        $this->cleanupPermissions('media', $this->lastMediaId);
        self::assertSame([], $this->remainingIds($Media->getTable(), $this->lastMediaId));
    }

    private function cleanupRemainingSites(): void
    {
        foreach ($this->remainingIds($this->fixtureProject->table(), $this->lastSiteId) as $id) {
            $Site = new Edit($this->fixtureProject, $id);

            if (!$Site->getAttribute('deleted')) {
                $Site->delete();
            }

            $Site->destroy();
        }

        $this->cleanupPermissions('sites', $this->lastSiteId);
        self::assertSame([], $this->remainingIds($this->fixtureProject->table(), $this->lastSiteId));
    }

    /** @return list<int> */
    private function remainingIds(string $table, int $lastId): array
    {
        $ids = QUI::getDataBaseConnection()->createQueryBuilder()
            ->select('id')
            ->from(QUI\Utils\Doctrine::quoteIdentifier($table))
            ->where('id > :lastId')
            ->setParameter('lastId', $lastId)
            ->orderBy('id', 'DESC')
            ->executeQuery()
            ->fetchFirstColumn();

        return array_map('intval', $ids);
    }

    private function cleanupPermissions(string $area, int $lastId): void
    {
        // Also remove ACLs for folders that the individual test has already deleted.
        QUI::getDataBaseConnection()->createQueryBuilder()
            ->delete(QUI\Utils\Doctrine::quoteIdentifier(QUI::getPermissionManager()::table() . '2' . $area))
            ->where('project = :project')
            ->andWhere('id > :lastId')
            ->setParameter('project', $this->fixtureProject->getName())
            ->setParameter('lastId', $lastId)
            ->executeStatement();
    }
}
