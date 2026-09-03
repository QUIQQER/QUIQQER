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

    protected static function checkGroupAssignmentPermission(Group $Group): void
    {
        $RequestUser = Server::getRequestUser();

        if ($RequestUser->isSU()) {
            return;
        }

        $rootGroupId = QUI::conf('globals', 'root');

        if (
            (string)$Group->getUUID() === (string)$rootGroupId
            || (string)$Group->getId() === (string)$rootGroupId
        ) {
            throw new QUI\Permissions\Exception(
                'Only superusers may assign users to the root group.',
                403
            );
        }

        $PermissionManager = QUI::getPermissionManager();
        $permissionList = $PermissionManager->getPermissionList('groups');

        foreach ($PermissionManager->getPermissions($Group) as $permission => $groupValue) {
            if (empty($groupValue)) {
                continue;
            }

            $type = $permissionList[$permission]['type'] ?? null;
            $userValue = Permission::hasPermission($permission, $RequestUser);

            if (
                is_string($type)
                && self::isPermissionWithinDelegationCeiling($type, $groupValue, $userValue)
            ) {
                continue;
            }

            throw new QUI\Permissions\Exception(
                'The selected group grants permissions that the current user may not delegate.',
                403,
                ['permission' => $permission]
            );
        }
    }

    private static function isPermissionWithinDelegationCeiling(
        string $type,
        mixed $groupValue,
        mixed $userValue
    ): bool {
        return match ($type) {
            'bool' => (bool)$userValue,
            'int' => is_numeric($userValue) && (int)$userValue >= (int)$groupValue,
            'array' => is_array($groupValue)
                && is_array($userValue)
                && array_diff($groupValue, $userValue) === [],
            'group', 'groups', 'user', 'users', 'users_and_groups' => self::isIdentifierListWithinDelegationCeiling(
                (string)$groupValue,
                is_string($userValue) || is_int($userValue) ? (string)$userValue : ''
            ),
            default => is_string($groupValue)
                && is_string($userValue)
                && hash_equals($groupValue, $userValue)
        };
    }

    private static function isIdentifierListWithinDelegationCeiling(string $groupValue, string $userValue): bool
    {
        $notEmpty = static fn(string $value): bool => $value !== '';
        $groupValues = array_filter(array_map('trim', explode(',', $groupValue)), $notEmpty);
        $userValues = array_filter(array_map('trim', explode(',', $userValue)), $notEmpty);

        return array_diff($groupValues, $userValues) === [];
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
