<?php

namespace QUI\Groups;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use QUI;
use Throwable;

class GroupDbalLifecycleTest extends TestCase
{
    private const TEST_PREFIX = 'codex-dbal-test-group-';

    public static function setUpBeforeClass(): void
    {
        self::skipIfDatabaseIsUnavailable();
        self::cleanupTestGroups();
    }

    protected function setUp(): void
    {
        self::skipIfDatabaseIsUnavailable();
    }

    protected function tearDown(): void
    {
        self::cleanupTestGroups();
    }

    public static function tearDownAfterClass(): void
    {
        self::cleanupTestGroups();
    }

    public function testGroupCanBeCreatedChangedActivatedDeactivatedAndDeleted(): void
    {
        $Groups = QUI::getGroups();
        $SystemUser = QUI::getUsers()->getSystemUser();
        $RootGroup = $Groups->get(QUI::conf('globals', 'root'));
        $name = self::TEST_PREFIX . uniqid();

        $Group = $RootGroup->createChild($name, $SystemUser);

        $this->assertSame($name, $Group->getName());
        $this->assertFalse($Group->isActive());

        $changedName = $name . '-changed';
        $Group->setAttribute('name', $changedName);
        $Group->save();

        $ReloadedGroup = $Groups->get($Group->getUUID());
        $this->assertSame($changedName, $ReloadedGroup->getName());

        $ReloadedGroup->activate();
        $this->assertTrue($Groups->get($ReloadedGroup->getUUID())->isActive());

        $ReloadedGroup->deactivate();
        $this->assertFalse($Groups->get($ReloadedGroup->getUUID())->isActive());

        $deletedGroupUuid = $ReloadedGroup->getUUID();
        $ReloadedGroup->delete();

        $this->assertFalse($this->groupRowExists($deletedGroupUuid));
    }

    public function testGroupCannotBeMovedBelowItsDescendant(): void
    {
        $Groups = QUI::getGroups();
        $SystemUser = QUI::getUsers()->getSystemUser();
        $RootGroup = $Groups->get(QUI::conf('globals', 'root'));
        $ParentGroup = $RootGroup->createChild(
            self::TEST_PREFIX . 'parent-' . uniqid(),
            $SystemUser
        );
        $ChildGroup = $ParentGroup->createChild(
            self::TEST_PREFIX . 'child-' . uniqid(),
            $SystemUser
        );

        try {
            $ParentGroup->setParent($ChildGroup->getUUID());
            self::fail('A group was moved below its own descendant.');
        } catch (Exception $Exception) {
            self::assertSame(400, $Exception->getCode());
        }

        self::assertSame(
            $RootGroup->getUUID(),
            $this->getGroupParentUuid($ParentGroup->getUUID())
        );
    }

    public function testGroupCannotBeItsOwnParent(): void
    {
        $Groups = QUI::getGroups();
        $SystemUser = QUI::getUsers()->getSystemUser();
        $RootGroup = $Groups->get(QUI::conf('globals', 'root'));
        $Group = $RootGroup->createChild(
            self::TEST_PREFIX . 'self-parent-' . uniqid(),
            $SystemUser
        );

        try {
            $Group->setParent($Group->getUUID());
            self::fail('A group was set as its own parent.');
        } catch (Exception $Exception) {
            self::assertSame(400, $Exception->getCode());
        }

        self::assertSame(
            $RootGroup->getUUID(),
            $this->getGroupParentUuid($Group->getUUID())
        );
    }

    public function testHierarchyTraversalStopsAtExistingCycle(): void
    {
        $Groups = QUI::getGroups();
        $SystemUser = QUI::getUsers()->getSystemUser();
        $RootGroup = $Groups->get(QUI::conf('globals', 'root'));
        $ParentGroup = $RootGroup->createChild(
            self::TEST_PREFIX . 'cycle-parent-' . uniqid(),
            $SystemUser
        );
        $ChildGroup = $ParentGroup->createChild(
            self::TEST_PREFIX . 'cycle-child-' . uniqid(),
            $SystemUser
        );

        $this->setGroupParentUuid($ParentGroup->getUUID(), $ChildGroup->getUUID());

        try {
            $CyclicGroup = new Group($ParentGroup->getUUID());

            self::assertSame([$ChildGroup->getUUID()], $CyclicGroup->getParentIds());
            self::assertSame([$ChildGroup->getUUID()], $CyclicGroup->getChildrenIds(true));
        } finally {
            $this->setGroupParentUuid($ParentGroup->getUUID(), $RootGroup->getUUID());
        }
    }

    private static function skipIfDatabaseIsUnavailable(): void
    {
        try {
            self::getConnection()->executeQuery(
                'SELECT 1 FROM ' . QUI\Utils\Doctrine::quoteIdentifier(Manager::table()) . ' LIMIT 1'
            )->free();
        } catch (Throwable $Exception) {
            self::markTestSkipped('QUIQQER database is not available: ' . $Exception->getMessage());
        }
    }

    private static function getConnection(): Connection
    {
        return QUI::getDataBaseConnection();
    }

    private static function cleanupTestGroups(): void
    {
        try {
            $Connection = self::getConnection();
            $Platform = $Connection->getDatabasePlatform();
            $groupsTable = QUI\Utils\Doctrine::quoteIdentifier(Manager::table());

            $Connection->createQueryBuilder()
                ->delete($groupsTable)
                ->where($Platform->quoteSingleIdentifier('name') . ' LIKE :name')
                ->setParameter('name', self::TEST_PREFIX . '%')
                ->executeStatement();
        } catch (Throwable) {
            // The availability check reports DB problems. Cleanup should not hide the test result.
        }
    }
    private function groupRowExists(string $uuid): bool
    {
        $Connection = self::getConnection();
        $Platform = $Connection->getDatabasePlatform();

        $row = $Connection->createQueryBuilder()
            ->select('uuid')
            ->from(QUI\Utils\Doctrine::quoteIdentifier(Manager::table()))
            ->where($Platform->quoteSingleIdentifier('uuid') . ' = :uuid')
            ->setParameter('uuid', $uuid)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return !empty($row);
    }

    private function getGroupParentUuid(string $uuid): string
    {
        $Connection = self::getConnection();
        $Platform = $Connection->getDatabasePlatform();

        return (string)$Connection->createQueryBuilder()
            ->select('parent')
            ->from(QUI\Utils\Doctrine::quoteIdentifier(Manager::table()))
            ->where($Platform->quoteSingleIdentifier('uuid') . ' = :uuid')
            ->setParameter('uuid', $uuid)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();
    }

    private function setGroupParentUuid(string $uuid, string $parentUuid): void
    {
        self::getConnection()->update(
            QUI\Utils\Doctrine::quoteIdentifier(Manager::table()),
            ['parent' => $parentUuid],
            ['uuid' => $uuid]
        );
    }
}
