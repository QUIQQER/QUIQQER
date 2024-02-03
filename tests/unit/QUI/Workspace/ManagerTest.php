<?php

namespace QUI\Workspace;

use PHPUnit\Framework\TestCase;
use QUI\Exception;
use QUI\Users\User;

class ManagerTest extends TestCase
{
    public function testSetup()
    {
        $this->markTestIncomplete('Figure out how to test this');
    }

    public function testCleanup()
    {
        $this->markTestIncomplete('Figure out how to test this');
    }

    public function testGetWorkspacesByUser()
    {
        $this->markTestIncomplete('Decide if this has to be tested or if it is trivial');
    }

    public function testGetWorkspaceByInvalidIdAndUserThrowsException()
    {
        $sut = new Manager();
        $testUser = $this->createUserStub();

        $this->expectException(Exception::class);
        $sut::getWorkspaceById(99999999, $testUser);
    }

    public function testAddWorkspaceWithInvalidUserThrowsException()
    {
        $sut = new Manager();
        $testUser = $this->createStub(\QUI\Interfaces\Users\User::class);

        $this->expectException(Exception::class);
        $sut::addWorkspace($testUser, 'title', 'data', 100, 100);
    }

    public function testAddAndGetWorkspace()
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

        $this->assertEquals($testUser->getId(), $testWorkspace['uid']);
        $this->assertEquals($testTitle, $testWorkspace['title']);
        $this->assertEquals($testData, $testWorkspace['data']);
        $this->assertEquals($testMinHeight, $testWorkspace['minHeight']);
        $this->assertEquals($testMinWidth, $testWorkspace['minWidth']);

        $sut::deleteWorkspace($testWorkspaceId, $testUser);
    }

    public function testSaveWorkspace()
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
        $this->assertEquals($testUser->getId(), $savedWorkspace['uid']);
        $this->assertEquals($newTitle, $savedWorkspace['title']);
        $this->assertEquals($newData, $savedWorkspace['data']);
        $this->assertEquals($newMinHeight, $savedWorkspace['minHeight']);
        $this->assertEquals($newMinWidth, $savedWorkspace['minWidth']);

        $sut::deleteWorkspace($testWorkspaceId, $testUser);
    }

    public function testSaveWorkspaceWithBigData()
    {
        $sut = new Manager();
        $testUser = $this->createUserStub();

        $this->expectException(Exception::class);
        $sut::saveWorkspace($testUser, 1, ['data' => str_repeat('a', 30000)]);
    }

    public function testDeleteWorkspace()
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

    public function testSetStandardWorkspace()
    {
        $this->markTestSkipped('Test skipped: getWorkspaceById does not accept user interface, making testing harder (see quiqqer/quiqqer#1336)');

        // Arrange
        $sut = new Manager();
        $testUser = \QUI::getUsers()->getSystemUser();  // cant use user stub here as the user has to be an admin
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
        $this->assertEquals(0 ,$testWorkspace['standard']);

        // Cleanup
        $sut::deleteWorkspace($testWorkspaceId, $testUser);
        $sut::deleteWorkspace($testWorkspaceToBecomeStandardId, $testUser);
    }

    public function testGetAvailablePanels()
    {
        $this->markTestIncomplete('Figure out how to test this');
    }

    protected function createUserStub(?int $userId = null): User
    {
        if (is_null($userId)) {
            $userId = random_int(9999, 999999);
        }

        return $this->createConfiguredStub(User::class, [
            'getId' => $userId
        ]);
    }
}
