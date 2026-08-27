<?php

namespace QUI\MCP\Permissions;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\Permissions\Manager;
use QUI\Permissions\Permission;
use QUI\Users\User;
use Throwable;

class GetEffectivePermission extends AbstractPermissionTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                int | string $user,
                string $permission,
                array | null $target = null
            ): CallToolResult | array {
                try {
                    self::checkPermissionAdministration();
                    $User = self::getManagedUser($user);
                    $Manager = QUI::getPermissionManager();
                    $targetType = (string)($target['type'] ?? 'global');
                    $TargetObject = null;
                    $targetData = ['type' => 'global'];

                    switch ($targetType) {
                        case 'global':
                            $area = 'user';
                            break;

                        case 'project':
                            $project = self::requireTargetString($target, 'project');
                            $lang = self::getTargetLanguage($target);
                            $TargetObject = self::getManagedProject($project, $lang);
                            $area = 'project';
                            $targetData = [
                                'type' => 'project',
                                'project' => $TargetObject->getName(),
                                'lang' => $TargetObject->getLang()
                            ];
                            break;

                        case 'site':
                            $project = self::requireTargetString($target, 'project');
                            $id = self::requireTargetId($target);
                            $lang = self::getTargetLanguage($target);
                            $TargetObject = self::getManagedSite($project, $id, $lang);
                            $Project = $TargetObject->getProject();
                            $area = 'site';
                            $targetData = [
                                'type' => 'site',
                                'project' => $Project->getName(),
                                'lang' => $Project->getLang(),
                                'id' => $TargetObject->getId()
                            ];
                            break;

                        case 'media':
                            $project = self::requireTargetString($target, 'project');
                            $id = self::requireTargetId($target);
                            $TargetObject = self::getManagedMedia($project, $id);
                            $area = 'media';
                            $targetData = [
                                'type' => 'media',
                                'project' => $TargetObject->getProject()->getName(),
                                'id' => $TargetObject->getId()
                            ];
                            break;

                        default:
                            throw new QUI\Exception('Unknown permission target type: ' . $targetType);
                    }

                    $definitions = $Manager->getPermissionList($area);

                    if (!isset($definitions[$permission])) {
                        throw new QUI\Exception(
                            'Unknown permission for area "' . $area . '": ' . $permission
                        );
                    }

                    $definition = $definitions[$permission];
                    $type = (string)($definition['type'] ?? 'bool');
                    $configuredValue = null;
                    $directUserPermissions = $Manager->getUserPermissionData($User);
                    $directUserValue = $directUserPermissions[$permission] ?? null;
                    $groups = [];

                    foreach ($User->getGroups() as $Group) {
                        $groups[] = [
                            'uuid' => $Group->getUUID(),
                            'name' => $Group->getName(),
                            'value' => $Manager->getPermissions($Group)[$permission] ?? null
                        ];
                    }

                    if ($TargetObject === null) {
                        $value = self::getEffectiveUserPermission(
                            $Manager,
                            $User,
                            $permission,
                            $type,
                            $groups
                        );
                    } else {
                        $configuredValue = $Manager->getPermissions($TargetObject)[$permission] ?? null;
                        $value = self::hasObjectPermission(
                            $targetType,
                            $permission,
                            $TargetObject,
                            $User
                        );
                    }

                    return [
                        'user' => [
                            'uuid' => $User->getUUID(),
                            'username' => $User->getUsername()
                        ],
                        'permission' => $permission,
                        'definition' => self::parsePermissionDefinitions([
                            $permission => $definition
                        ])[0],
                        'target' => $targetData,
                        'value' => $value,
                        'configuredTargetValue' => $configuredValue,
                        'directUserValue' => $directUserValue,
                        'groupValues' => $groups
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_permissions_effective_get',
            description: 'Returns a user permission after group and optional object ACL evaluation.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['user', 'permission'],
                'properties' => [
                    'user' => self::getSubjectIdSchema('user'),
                    'permission' => ['type' => 'string', 'minLength' => 1],
                    'target' => [
                        'type' => ['object', 'null'],
                        'additionalProperties' => false,
                        'properties' => [
                            'type' => [
                                'type' => 'string',
                                'enum' => ['global', 'project', 'site', 'media']
                            ],
                            'project' => ['type' => 'string', 'minLength' => 1],
                            'lang' => ['type' => ['string', 'null']],
                            'id' => ['type' => 'integer', 'minimum' => 1]
                        ]
                    ]
                ]
            ]
        );
    }

    /**
     * @param array<string, mixed>|null $target
     */
    private static function requireTargetString(?array $target, string $key): string
    {
        $value = $target[$key] ?? null;

        if (!is_string($value) || $value === '') {
            throw new QUI\Exception('Permission target requires a non-empty "' . $key . '".');
        }

        return $value;
    }

    /**
     * @param array<string, mixed>|null $target
     */
    private static function requireTargetId(?array $target): int
    {
        $id = $target['id'] ?? null;

        if (!is_int($id) || $id < 1) {
            throw new QUI\Exception('Permission target requires a positive integer "id".');
        }

        return $id;
    }

    /**
     * @param array<string, mixed>|null $target
     */
    private static function getTargetLanguage(?array $target): ?string
    {
        $lang = $target['lang'] ?? null;

        if ($lang !== null && !is_string($lang)) {
            throw new QUI\Exception('Permission target "lang" must be a string or null.');
        }

        return $lang;
    }

    private static function hasProjectPermission(
        string $permission,
        QUI\Projects\Project $Project,
        QUI\Users\User $User
    ): bool {
        try {
            return Permission::checkProjectPermission($permission, $Project, $User);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param array<int, array{uuid: int|string, name: string, value: mixed}> $groups
     */
    private static function getEffectiveUserPermission(
        Manager $Manager,
        User $User,
        string $permission,
        string $type,
        array $groups
    ): mixed {
        $directPermissions = $Manager->getUserPermissionData($User);

        if ($type !== 'int' && array_key_exists($permission, $directPermissions)) {
            return $directPermissions[$permission];
        }

        $values = array_column($groups, 'value');

        if ($type === 'int' && array_key_exists($permission, $directPermissions)) {
            $values[] = $directPermissions[$permission];
        }

        $result = false;

        foreach ($values as $value) {
            if ($value === true) {
                return true;
            }

            if (is_numeric($value)) {
                $value = (int)$value;

                if (is_bool($result) || $value > $result) {
                    $result = $value;
                }

                continue;
            }

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return $result;
    }

    private static function hasObjectPermission(
        string $targetType,
        string $permission,
        object $Target,
        User $User
    ): bool {
        if ($targetType === 'project' && $Target instanceof QUI\Projects\Project) {
            return self::hasProjectPermission($permission, $Target, $User);
        }

        if ($targetType === 'site' && $Target instanceof QUI\Projects\Site\Edit) {
            return Permission::hasSitePermission($permission, $Target, $User);
        }

        if ($targetType === 'media' && $Target instanceof QUI\Projects\Media\Item) {
            return Permission::hasMediaPermission($permission, $Target, $User);
        }

        throw new QUI\Exception('Permission target could not be evaluated.');
    }
}
