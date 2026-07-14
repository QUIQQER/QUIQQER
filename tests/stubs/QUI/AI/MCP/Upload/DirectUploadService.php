<?php

namespace QUI\AI\MCP\Upload;

if (!class_exists(DirectUploadService::class)) {
    class DirectUploadService
    {
        public const STATUS_UPLOADED = 'uploaded';

        /**
         * @param array<string, mixed> $metadata
         * @param string[]|null $allowedMimeTypes
         * @return array<string, mixed>
         */
        public function createSession(
            string $filename,
            array $metadata,
            ?int $maxBytes = null,
            ?array $allowedMimeTypes = null
        ): array {
            return [];
        }

        /**
         * @return array<string, mixed>
         */
        public function getOwnedSession(string $uploadId): array
        {
            return [];
        }

        /**
         * @return array<string, mixed>
         */
        public function getSessionStatus(string $uploadId): array
        {
            return [];
        }

        public function deleteSession(string $uploadId): void
        {
        }
    }
}
