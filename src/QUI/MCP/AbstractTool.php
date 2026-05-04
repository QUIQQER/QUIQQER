<?php

/**
 * This file contains the \QUI\MCP\AbstractTool
 */

namespace QUI\MCP;

use QUI;
use QUI\AI\MCP\Server;
use QUI\Interfaces\Projects\Media\File as MediaFile;
use QUI\Permissions\Permission;
use QUI\Projects\Project;
use QUI\Projects\Site;
use QUI\Projects\Site\Edit;

abstract class AbstractTool implements ToolInterface
{
    protected const CORE_MCP_PERMISSION = 'quiqqer.core.mcp';

    protected static function checkCorePermission(): void
    {
        Permission::checkPermission(
            self::CORE_MCP_PERMISSION,
            Server::getRequestUser()
        );
    }

    protected static function getProject(string $project, ?string $lang = null): Project
    {
        if (empty($lang)) {
            return QUI::getProject($project);
        }

        return QUI::getProject($project, $lang);
    }

    protected static function getEditSite(string $project, int $siteId, ?string $lang = null): Edit
    {
        return new Edit(self::getProject($project, $lang), $siteId);
    }

    protected static function parseProject(Project $Project): array
    {
        return [
            'name' => $Project->getName(),
            'title' => $Project->getTitle(),
            'lang' => $Project->getLang(),
            'defaultLang' => $Project->getAttribute('default_lang'),
            'languages' => $Project->getLanguages()
        ];
    }

    protected static function parseSite(Site $Site, bool $withAttributes = false): array
    {
        $Project = $Site->getProject();
        $result = [
            'id' => $Site->getId(),
            'project' => $Project->getName(),
            'lang' => $Project->getLang(),
            'parentId' => $Site->getParentId(),
            'name' => $Site->getAttribute('name'),
            'title' => $Site->getAttribute('title'),
            'short' => $Site->getAttribute('short'),
            'type' => $Site->getAttribute('type'),
            'active' => (bool)$Site->getAttribute('active'),
            'url' => $Site->getUrlRewritten()
        ];

        if ($withAttributes) {
            $result['attributes'] = $Site->getAttributes();
        }

        return $result;
    }

    protected static function parseMediaItem(MediaFile $Item, bool $withAttributes = false): array
    {
        $Project = $Item->getProject();
        $result = [
            'id' => $Item->getId(),
            'project' => $Project->getName(),
            'name' => $Item->getAttribute('name'),
            'type' => $Item->getAttribute('type'),
            'mimeType' => $Item->getAttribute('mime_type'),
            'title' => $Item->getAttribute('title'),
            'short' => $Item->getAttribute('short'),
            'url' => $Item->getUrl(true)
        ];

        if ($withAttributes) {
            $result['attributes'] = $Item->getAttributes();
        }

        return $result;
    }

    protected static function parseLimit(?int $limit, ?int $offset = null): string
    {
        return ((int)max(0, $offset ?? 0)) . ',' . self::sanitizeLimit($limit);
    }

    protected static function sanitizeLimit(?int $limit): int
    {
        if (empty($limit)) {
            return 50;
        }

        return (int)min(100, max(1, $limit));
    }
}
