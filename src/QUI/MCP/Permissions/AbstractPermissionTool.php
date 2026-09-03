<?php

/**
 * This file contains the \QUI\MCP\Permissions\AbstractPermissionTool
 */

namespace QUI\MCP\Permissions;

use QUI;
use QUI\AI\MCP\Server;
use QUI\Groups\Group;
use QUI\MCP\AbstractTool;
use QUI\Permissions\Manager;
use QUI\Projects\Media\Item as MediaItem;
use QUI\Projects\Project;
use QUI\Projects\Site\Edit;
use QUI\Users\User;
use Ramsey\Uuid\Uuid;

abstract class AbstractPermissionTool extends AbstractTool
{
    protected const PERMISSIONS_MCP_PERMISSION = 'quiqqer.core.mcp.permissions.canUse';

    protected static function checkPermissionAdministration(): void
    {
        self::checkCorePermission();
        self::checkPermission(self::PERMISSIONS_MCP_PERMISSION);
        self::checkPermission('quiqqer.system.permissions');
    }

    protected static function getManagedUser(int | string $user): User
    {
        $User = QUI::getUsers()->get($user);

        if (!$User instanceof User) {
            throw new QUI\Exception('The selected account is not a manageable QUIQQER user.', 400);
        }

        return $User;
    }

    protected static function getManagedGroup(int | string $group): Group
    {
        return QUI::getGroups()->get($group);
    }

    protected static function getManagedProject(string $project, ?string $lang = null): Project
    {
        return self::getProject($project, $lang);
    }

    protected static function getManagedSite(
        string $project,
        int $siteId,
        ?string $lang = null
    ): Edit {
        return self::getEditSite($project, $siteId, $lang);
    }

    protected static function getManagedMedia(string $project, int $mediaId): MediaItem
    {
        $Item = self::getProject($project)->getMedia()->get($mediaId);

        if (!$Item instanceof MediaItem) {
            throw new QUI\Exception('The selected object is not a manageable media item.', 400);
        }

        return $Item;
    }

    /**
     * @param array<string, mixed> $target
     * @return array<string, mixed>
     */
    protected static function getPermissionsResponse(object $Object, array $target): array
    {
        $Manager = QUI::getPermissionManager();
        $area = Manager::classToArea($Object::class);

        return [
            'target' => $target,
            'area' => $area,
            'permissions' => $Manager->getPermissions($Object),
            'definitions' => self::parsePermissionDefinitions(
                $Manager->getPermissionList($area)
            )
        ];
    }

    /**
     * @param array<array-key, mixed> $permissions
     * @param array<string, mixed> $target
     * @return array<string, mixed>
     */
    protected static function updatePermissions(
        User | Group | Project | Edit | MediaItem $Object,
        array $permissions,
        array $target
    ): array {
        $Manager = QUI::getPermissionManager();
        $area = Manager::classToArea($Object::class);
        $permissions = self::validatePermissionValues($permissions, $area);

        $Manager->setPermissions(
            $Object,
            $permissions,
            Server::getRequestUser()
        );

        return [
            'updated' => array_keys($permissions),
            'result' => self::getPermissionsResponse($Object, $target)
        ];
    }

    /**
     * @param array<array-key, mixed> $permissions
     * @return array<string, mixed>
     */
    protected static function validatePermissionValues(array $permissions, string $area): array
    {
        if ($permissions === []) {
            throw new QUI\Exception('At least one permission must be provided.');
        }

        $definitions = QUI::getPermissionManager()->getPermissionList($area);
        $result = [];

        foreach ($permissions as $permission => $value) {
            if (!is_string($permission) || !isset($definitions[$permission])) {
                throw new QUI\Exception(
                    'Unknown permission for area "' . $area . '": ' . (string)$permission
                );
            }

            $type = (string)($definitions[$permission]['type'] ?? 'bool');
            self::validatePermissionValue($permission, $type, $value);
            $result[$permission] = $value;
        }

        return $result;
    }

    protected static function validatePermissionValue(string $permission, string $type, mixed $value): void
    {
        $valid = match ($type) {
            'bool' => is_bool($value),
            'int' => is_int($value),
            'array' => is_array($value),
            'user', 'group' => is_string($value) && self::isIdentifierList($value, false, true),
            'users', 'groups' => is_string($value) && self::isIdentifierList($value),
            'users_and_groups' => is_string($value) && self::isIdentifierList($value, true),
            default => is_string($value)
        };

        if (!$valid) {
            throw new QUI\Exception(
                'Invalid value for permission "' . $permission . '": expected ' . $type . '.'
            );
        }
    }

    protected static function isIdentifierList(
        string $value,
        bool $withTypePrefix = false,
        bool $single = false
    ): bool {
        if ($value === '') {
            return true;
        }

        $identifiers = explode(',', $value);

        if ($single && count($identifiers) !== 1) {
            return false;
        }

        foreach ($identifiers as $identifier) {
            $identifier = trim($identifier);

            if ($withTypePrefix) {
                if (!str_starts_with($identifier, 'u') && !str_starts_with($identifier, 'g')) {
                    return false;
                }

                $identifier = substr($identifier, 1);
            }

            if ($identifier === '' || (!ctype_digit($identifier) && !Uuid::isValid($identifier))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, array<string, mixed>> $definitions
     * @return array<int, array<string, mixed>>
     */
    protected static function parsePermissionDefinitions(array $definitions): array
    {
        $result = [];

        foreach ($definitions as $name => $definition) {
            $area = (string)($definition['area'] ?? '');

            $result[] = [
                'name' => $name,
                'type' => (string)($definition['type'] ?? 'bool'),
                'area' => $area === '' ? 'global' : $area,
                'defaultValue' => $definition['defaultvalue'] ?? null,
                'source' => $definition['src'] ?? null,
                'title' => $definition['title'] ?? null,
                'description' => $definition['desc'] ?? null
            ];
        }

        return $result;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected static function getPermissionDefinitions(?string $area = null): array
    {
        $Manager = QUI::getPermissionManager();

        if ($area !== 'global') {
            return $Manager->getPermissionList($area ?? false);
        }

        return array_filter(
            $Manager->getPermissionList(),
            static fn(array $definition): bool => in_array(
                (string)($definition['area'] ?? ''),
                ['', 'global'],
                true
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getSubjectIdSchema(string $subject): array
    {
        return [
            'description' => ucfirst($subject) . ' UUID or legacy numeric ID.',
            'oneOf' => [
                ['type' => 'string', 'minLength' => 1],
                ['type' => 'integer', 'minimum' => 0]
            ]
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getPermissionsSchema(): array
    {
        return [
            'type' => 'object',
            'description' => 'Permission names mapped to their new values.',
            'minProperties' => 1,
            'additionalProperties' => true
        ];
    }
}
