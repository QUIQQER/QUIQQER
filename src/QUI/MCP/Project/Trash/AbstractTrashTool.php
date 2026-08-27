<?php

namespace QUI\MCP\Project\Trash;

use QUI;
use QUI\AI\MCP\Server;
use QUI\MCP\AbstractTool;
use QUI\Permissions\Permission;

abstract class AbstractTrashTool extends AbstractTool
{
    protected static function checkTrashPermission(): void
    {
        self::checkCorePermission();
        Permission::checkAdminUser(Server::getRequestUser());
    }

    /**
     * @param array<array-key, mixed> $ids
     * @return array<int, int>
     */
    protected static function validateIds(array $ids): array
    {
        if ($ids === []) {
            throw new QUI\Exception('At least one ID must be provided.');
        }

        $result = [];

        foreach ($ids as $id) {
            if (!is_int($id) || $id < 1) {
                throw new QUI\Exception('Every ID must be a positive integer.');
            }

            $result[$id] = $id;
        }

        return array_values($result);
    }

    protected static function requireConfirmation(bool $confirm): void
    {
        if (!$confirm) {
            throw new QUI\Exception('Permanent deletion requires confirm=true.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getIdListSchema(): array
    {
        return [
            'type' => 'array',
            'minItems' => 1,
            'uniqueItems' => true,
            'items' => ['type' => 'integer', 'minimum' => 1]
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getConfirmationSchema(): array
    {
        return [
            'type' => 'boolean',
            'const' => true,
            'description' => 'Must be true to confirm irreversible deletion.'
        ];
    }

    /**
     * @return array{limit: int, offset: int, params: array<string, mixed>}
     */
    protected static function getListParams(
        ?int $limit,
        ?int $offset,
        ?string $order,
        ?string $direction
    ): array {
        $limit = self::sanitizeLimit($limit);
        $offset = max(0, $offset ?? 0);

        return [
            'limit' => $limit,
            'offset' => $offset,
            'params' => [
                'limit' => $offset . ',' . $limit,
                'order' => $order ?: 'id',
                'sort' => $direction === 'ASC' ? 'ASC' : 'DESC'
            ]
        ];
    }
}
