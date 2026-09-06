<?php

namespace QUI\Workspace;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Exception;
use QUI\Interfaces\Users\User;
use QUI\Users\Nobody;
use QUI\Utils\Uuid;
use ReflectionProperty;

class ManagerTest extends TestCase
{
    private Connection $Connection;
    private ?Connection $previousConnection;

    protected function setUp(): void
    {
        $ConnectionProperty = new ReflectionProperty(QUI::class, 'QueryBuilder');
        $this->previousConnection = $ConnectionProperty->getValue();
        $this->Connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true
        ]);
        $ConnectionProperty->setValue(null, $this->Connection);
        Manager::setup();
    }

    protected function tearDown(): void
    {
        (new ReflectionProperty(QUI::class, 'QueryBuilder'))->setValue(null, $this->previousConnection);
        $this->Connection->close();
    }

    public function testSetup(): void
    {
        $this->markTestIncomplete('Figure out how to test this');
    }

    public function testCleanup(): void
    {
        $this->markTestIncomplete('Figure out how to test this');
    }

    public function testGetWorkspacesByUser(): void
    {
        $User = $this->createUserStub();
        $workspaceId = Manager::addWorkspace($User, 'Own workspace', '[]', 100, 200);
        Manager::addWorkspace($this->createUserStub(), 'Other workspace', '[]', 100, 200);

        $workspaces = Manager::getWorkspacesByUser($User);

        $this->assertCount(1, $workspaces);
        $this->assertEquals($workspaceId, $workspaces[0]['id']);
        $this->assertSame(['Own workspace'], Manager::getWorkspacesTitlesByUser($User));
    }

    public function testNobodyHasNoWorkspaceTitles(): void
    {
        $this->assertSame([], Manager::getWorkspacesTitlesByUser(new Nobody()));
    }

    public function testNobodyCannotAddWorkspace(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User is no administrator user');

        Manager::addWorkspace(new Nobody(), 'Not allowed', '[]', 100, 200);
    }

    public function testGetWorkspaceByInvalidIdAndUserThrowsException(): void
    {
        $sut = new Manager();
        $testUser = $this->createUserStub();

        $this->expectException(Exception::class);
        $this->expectExceptionCode(404);
        $sut::getWorkspaceById(99999999, $testUser);
    }

    public function testAddAndGetWorkspace(): void
    {
        $sut = new Manager();
        $testUser = $this->createUserStub();
        $testTitle = 'test_title';
        $testData = 'test_data';
        $testMinHeight = 123;
        $testMinWidth = 456;

        $testWorkspaceId = $sut::addWorkspace(
            User: $testUser,
            title: $testTitle,
            data: $testData,
            minHeight: $testMinHeight,
            minWidth: $testMinWidth
        );
        $testWorkspace = $sut::getWorkspaceById($testWorkspaceId, $testUser);

        $this->assertEquals($testUser->getUUID(), $testWorkspace['uid']);
        $this->assertEquals($testTitle, $testWorkspace['title']);
        $this->assertEquals($testData, $testWorkspace['data']);
        $this->assertEquals($testMinHeight, $testWorkspace['minHeight']);
        $this->assertEquals($testMinWidth, $testWorkspace['minWidth']);

        $sut::deleteWorkspace($testWorkspaceId, $testUser);
    }

    public function testSaveWorkspace(): void
    {
        $sut = new Manager();
        $testUser = $this->createUserStub();
        $testWorkspaceId = $sut::addWorkspace(
            User: $testUser,
            title: 'test_title',
            data: 'test_data',
            minHeight: 123,
            minWidth: 456
        );
        $newTitle = 'test_title_new';
        $newData = '[]';
        $newMinHeight = '789';
        $newMinWidth = '444';

        $sut::saveWorkspace($testUser, $testWorkspaceId, [
            'title' => $newTitle,
            'data' => $newData,
            'minHeight' => $newMinHeight,
            'minWidth' => $newMinWidth
        ]);

        $savedWorkspace = $sut::getWorkspaceById($testWorkspaceId, $testUser);
        $this->assertEquals($testUser->getUUID(), $savedWorkspace['uid']);
        $this->assertEquals($newTitle, $savedWorkspace['title']);
        $this->assertEquals($newData, $savedWorkspace['data']);
        $this->assertEquals($newMinHeight, $savedWorkspace['minHeight']);
        $this->assertEquals($newMinWidth, $savedWorkspace['minWidth']);

        $sut::deleteWorkspace($testWorkspaceId, $testUser);
    }

    public function testSaveWorkspaceWithBigData(): void
    {
        $sut = new Manager();
        $testUser = $this->createUserStub();
        $workspaceId = $sut::addWorkspace($testUser, 'Test workspace', '[]', 100, 200);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not save the workspace. Workspace is to big.');
        $sut::saveWorkspace($testUser, $workspaceId, ['data' => json_encode([str_repeat('a', 30000)])]);
    }

    public function testDeleteWorkspace(): void
    {
        $sut = new Manager();
        $testUser = $this->createUserStub();
        $testWorkspaceId = $sut::addWorkspace(
            User: $testUser,
            title: 'test_title',
            data: 'test_data',
            minHeight: 123,
            minWidth: 456
        );
        $sut::deleteWorkspace($testWorkspaceId, $testUser);

        $this->expectException(Exception::class);
        $sut::getWorkspaceById($testWorkspaceId, $testUser);
    }

    public function testSetStandardWorkspace(): void
    {
        // Arrange
        $sut = new Manager();
        $testUser = $this->createUserStub();
        $testWorkspaceToBecomeStandardId = $sut::addWorkspace(
            User: $testUser,
            title: 'test_title_to_become_standard',
            data: '[]',
            minHeight: 123,
            minWidth: 456
        );
        $testWorkspaceId = $sut::addWorkspace(
            User: $testUser,
            title: 'test_title',
            data: '[]',
            minHeight: 123,
            minWidth: 456
        );

        // Act
        $sut::setStandardWorkspace($testUser, $testWorkspaceId);
        $sut::setStandardWorkspace($testUser, $testWorkspaceToBecomeStandardId);

        // Assert
        $testWorkspace = $sut::getWorkspaceById($testWorkspaceId, $testUser);
        $testWorkspaceToBecomeStandard = $sut::getWorkspaceById($testWorkspaceToBecomeStandardId, $testUser);
        $this->assertEquals(1, $testWorkspaceToBecomeStandard['standard']);
        $this->assertEquals(0, $testWorkspace['standard']);

        // Cleanup
        $sut::deleteWorkspace($testWorkspaceId, $testUser);
        $sut::deleteWorkspace($testWorkspaceToBecomeStandardId, $testUser);
    }

    public function testUserCannotReadForeignWorkspace(): void
    {
        $Owner = $this->createUserStub();
        $workspaceId = Manager::addWorkspace($Owner, 'Private workspace', '[]', 100, 200);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(404);

        Manager::getWorkspaceById($workspaceId, $this->createUserStub());
    }

    public function testUserCannotSaveForeignWorkspace(): void
    {
        $Owner = $this->createUserStub();
        $workspaceId = Manager::addWorkspace($Owner, 'Private workspace', '[]', 100, 200);

        try {
            Manager::saveWorkspace($this->createUserStub(), $workspaceId, ['title' => 'Changed']);
            $this->fail('Saving another user\'s workspace must fail.');
        } catch (Exception $Exception) {
            $this->assertSame(404, $Exception->getCode());
        }

        $this->assertSame('Private workspace', Manager::getWorkspaceById($workspaceId, $Owner)['title']);
    }

    public function testUserCannotDeleteForeignWorkspace(): void
    {
        $Owner = $this->createUserStub();
        $workspaceId = Manager::addWorkspace($Owner, 'Private workspace', '[]', 100, 200);

        Manager::deleteWorkspace($workspaceId, $this->createUserStub());

        $this->assertSame('Private workspace', Manager::getWorkspaceById($workspaceId, $Owner)['title']);
    }

    public function testSettingStandardWorkspaceDoesNotChangeOtherUsersWorkspaces(): void
    {
        $Owner = $this->createUserStub();
        $OtherUser = $this->createUserStub();
        $workspaceId = Manager::addWorkspace($Owner, 'Own workspace', '[]', 100, 200);
        $otherWorkspaceId = Manager::addWorkspace($OtherUser, 'Other workspace', '[]', 100, 200);
        Manager::setStandardWorkspace($OtherUser, $otherWorkspaceId);

        Manager::saveWorkspace($Owner, $workspaceId, ['standard' => 1]);

        $this->assertEquals(1, Manager::getWorkspaceById($workspaceId, $Owner)['standard']);
        $this->assertEquals(1, Manager::getWorkspaceById($otherWorkspaceId, $OtherUser)['standard']);
    }

    public function testGetAvailablePanels(): void
    {
        $this->markTestIncomplete('Figure out how to test this');
    }

    protected function createUserStub(?string $userUuid = null): User
    {
        if (is_null($userUuid)) {
            $userUuid = Uuid::get();
        }

        return $this->createConfiguredStub(User::class, [
            'getUUID' => $userUuid,
            'canUseBackend' => true,
            'isSU' => true
        ]);
    }
}
