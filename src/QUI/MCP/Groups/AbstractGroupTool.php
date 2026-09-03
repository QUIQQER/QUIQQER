<?php

/**
 * This file contains the \QUI\MCP\Groups\AbstractGroupTool
 */

namespace QUI\MCP\Groups;

use QUI;
use QUI\AI\MCP\Server;
use QUI\Groups\Group;
use QUI\MCP\Users\AbstractUserTool;
use QUI\Permissions\Permission;

abstract class AbstractGroupTool extends AbstractUserTool
{
    protected const GROUPS_MCP_PERMISSION = 'quiqqer.core.mcp.groups.canUse';

    protected const UPDATE_ATTRIBUTES = [
        'name',
        'toolbar',
        'assigned_toolbar',
        'avatar'
    ];

    protected static function checkGroupPermission(string $permission): void
    {
        self::checkCorePermission();
        self::checkPermission(self::GROUPS_MCP_PERMISSION);
        self::checkPermission($permission);
    }

    protected static function checkMembershipPermission(): void
    {
        self::checkGroupPermission('quiqqer.admin.groups.edit');
        self::checkPermission('quiqqer.core.mcp.users.canUse');
        self::checkPermission('quiqqer.admin.users.edit');
    }

    protected static function checkMembershipReadPermission(): void
    {
        self::checkGroupPermission('quiqqer.admin.groups.view');
        self::checkPermission(self::USERS_MCP_PERMISSION);
        self::checkPermission('quiqqer.admin.users.view');
    }

    protected static function checkGroupDeletePermission(): void
    {
        self::checkCorePermission();
        self::checkPermission(self::GROUPS_MCP_PERMISSION);
        Permission::checkSU(Server::getRequestUser());
    }

    protected static function getGroup(int | string $groupId): Group
    {
        return QUI::getGroups()->get($groupId);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function parseGroup(Group $Group): array
    {
        return [
            'id' => $Group->getId(),
            'uuid' => $Group->getUUID(),
            'name' => $Group->getName(),
            'parent' => $Group->getAttribute('parent'),
            'active' => $Group->isActive(),
            'userCount' => $Group->countUser(),
            'hasChildren' => (bool)$Group->hasChildren()
        ];
    }

    /**
     * @return array{groups: array<int, array<string, mixed>>, total: int, limit: int, offset: int}
     */
    protected static function findGroups(?string $query, int $limit, int $offset): array
    {
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        $params = [
            'limit' => $limit,
            'start' => $offset,
            'field' => 'name',
            'order' => 'ASC'
        ];

        if ($query !== null && trim($query) !== '') {
            $params['search'] = trim($query);
        }

        $Groups = QUI::getGroups();
        $rows = $Groups->search($params);
        $result = [];

        foreach ($rows as $row) {
            if (!isset($row['uuid']) && !isset($row['id'])) {
                continue;
            }

            try {
                $result[] = self::parseGroup(
                    $Groups->get((string)($row['uuid'] ?? $row['id']))
                );
            } catch (QUI\Exception) {
            }
        }

        return [
            'groups' => $result,
            'total' => $Groups->count($params),
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    /**
     * @param array<array-key, mixed> $attributes
     * @return array{attributes: array<string, mixed>, ignored: array<int, string>}
     */
    protected static function filterGroupAttributes(array $attributes): array
    {
        $valid = [];
        $ignored = [];

        foreach ($attributes as $attribute => $value) {
            if (
                !is_string($attribute)
                || !in_array($attribute, self::UPDATE_ATTRIBUTES, true)
                || (!is_scalar($value) && $value !== null)
            ) {
                $ignored[] = is_string($attribute) ? $attribute : (string)$attribute;
                continue;
            }

            $valid[$attribute] = $value;
        }

        return [
            'attributes' => $valid,
            'ignored' => $ignored
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getGroupIdSchema(): array
    {
        return [
            'description' => 'Group UUID or legacy numeric ID.',
            'oneOf' => [
                ['type' => 'string', 'minLength' => 1],
                ['type' => 'integer', 'minimum' => 0]
            ]
        ];
    }
}
