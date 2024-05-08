<?php

namespace QUI;

use PHPUnit\Framework\TestCase;

class TempTest extends TestCase
{
    protected function getTestDirectoryPath(): string
    {
        return '/tmp/quiqqer_quiqqer_test_directory_' . uniqid();
    }

    public function testConstructorCreatesFolder()
    {
        $testDirectory = $this->getTestDirectoryPath();

        new Temp($testDirectory);

        $this->assertDirectoryExists($testDirectory);
        rmdir($testDirectory);
    }

    public function testClearRemovesFilesFromDirectory()
    {
        $testDirectory = $this->getTestDirectoryPath();
        $sut = new Temp($testDirectory);
        file_put_contents($testDirectory . '/test_file', 'hello world');

        $sut->clear();

        $isFileInTestDirectory = (new \FilesystemIterator($testDirectory, \FilesystemIterator::SKIP_DOTS))->valid();
        $this->assertFalse($isFileInTestDirectory);
    }

    public function testMoveToTemp()
    {
        $testDirectoryPath = $this->getTestDirectoryPath();
        $sut = new Temp($testDirectoryPath);
        $directoryToMove = $this->getTestDirectoryPath();
        mkdir($directoryToMove);

        $sut->moveToTemp($directoryToMove);

        // We don't know where the file is moved, so just check if it's gone
        $this->assertFileDoesNotExist($directoryToMove);
    }

    public function testCreateFolderWithGivenName()
    {
        $sut = new Temp($this->getTestDirectoryPath());

        $cacheFolder = $sut->createFolder('test_folder');

        $this->assertDirectoryExists($cacheFolder);
    }

    public function testCreateFolderWithoutName()
    {
        $sut = new Temp($this->getTestDirectoryPath());

        $cacheFolder = $sut->createFolder();

        $this->assertDirectoryExists($cacheFolder);
    }
}
