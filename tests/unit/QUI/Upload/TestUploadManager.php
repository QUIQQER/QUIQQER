<?php

namespace QUI\Upload;

use QUI\Interfaces\Users\User;
use QUI\QDOM;

class TestUploadManager extends Manager
{
    public function __construct(private readonly string $testRoot)
    {
    }

    public function getDir(): string
    {
        return $this->testRoot . '/uploads/';
    }

    protected function getUserUploadDir(null | User $User = null): string
    {
        return $this->getDir() . 'test-user/';
    }

    /**
     * @return array{filename: string, directory: string, file: string, metadata: string}
     */
    public function paths(mixed $filename): array
    {
        return $this->getUploadPaths($filename);
    }

    public function userDirectory(): string
    {
        return $this->getDir() . 'test-user';
    }

    public function append(string $filename, string $data): void
    {
        $this->appendUploadData($filename, $data);
    }

    public function runCallback(string $function): mixed
    {
        return $this->callFunction($function);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function addMetadata(string $filename, array $params): void
    {
        $this->add($filename, $params);
    }

    public function readMetadata(string $filename): QDOM
    {
        return $this->getFileData($filename);
    }

    public function validateAndCheckAllowed(
        mixed $filename,
        string $fileType,
        mixed $allowedTypes,
        mixed $allowedEndings
    ): void {
        $filename = $this->validateUploadFilename($filename);
        $this->checkAllowedUpload($filename, $fileType, $allowedTypes, $allowedEndings);
    }

    public function runFormUpload(mixed $allowedTypes, mixed $allowedEndings): void
    {
        $this->formUpload(static function (): void {
        }, [], $allowedTypes, $allowedEndings);
    }
}
