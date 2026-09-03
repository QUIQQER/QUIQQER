<?php

namespace QUI\MCP\Permissions;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class GetProjectPermissions extends AbstractPermissionTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, string | null $lang = null): CallToolResult | array {
                try {
                    self::checkPermissionAdministration();
                    $Project = self::getManagedProject($project, $lang);

                    return self::getPermissionsResponse($Project, [
                        'type' => 'project',
                        'project' => $Project->getName(),
                        'lang' => $Project->getLang()
                    ]);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_permissions_project_get',
            description: 'Returns configured permission values of one project language.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'lang' => ['type' => ['string', 'null']]
                ]
            ]
        );
    }
}
