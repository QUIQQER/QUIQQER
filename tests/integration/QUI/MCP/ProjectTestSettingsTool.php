<?php

declare(strict_types=1);

namespace QUI\MCP;

use Mcp\Server\Builder;
use QUI\MCP\Project\AbstractProjectSettingsTool;
use QUI\Projects\Project;

class ProjectTestSettingsTool extends AbstractProjectSettingsTool
{
    public function register(Builder $serverBuilder): void
    {
    }

    /**
     * @return array<string, mixed>
     */
    public static function get(Project $Project, string $key): array
    {
        return parent::getSetting($Project, $key);
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    public static function update(Project $Project, array $settings): array
    {
        return parent::updateSettings($Project, $settings);
    }
}
