<?php

/**
 * This file contains the \QUI\MCP\Provider
 */

namespace QUI\MCP;

use Mcp\Server\Builder;
use QUI\AI\MCP\ProviderInterface;
use QUI\AI\MCP\Server;
use QUI\MCP\Project\GetCustomCSS;
use QUI\MCP\Project\GetCustomJavaScript;
use QUI\MCP\Project\ListProjects;
use QUI\MCP\Project\Media\ActivateMedia;
use QUI\MCP\Project\Media\CreateFolder;
use QUI\MCP\Project\Media\DeactivateMedia;
use QUI\MCP\Project\Media\DeleteMedia;
use QUI\MCP\Project\Media\GetMedia;
use QUI\MCP\Project\Media\ListMedia;
use QUI\MCP\Project\Media\SearchMedia;
use QUI\MCP\Project\Media\UpdateMedia;
use QUI\MCP\Project\Media\UploadMedia;
use QUI\MCP\Project\SetCustomCSS;
use QUI\MCP\Project\SetCustomJavaScript;
use QUI\MCP\Project\Sites\ActivateSite;
use QUI\MCP\Project\Sites\AddLanguageLink;
use QUI\MCP\Project\Sites\CopySite;
use QUI\MCP\Project\Sites\CopySiteToLanguage;
use QUI\MCP\Project\Sites\CreateChild;
use QUI\MCP\Project\Sites\DeactivateSite;
use QUI\MCP\Project\Sites\DeleteSite;
use QUI\MCP\Project\Sites\GetSite;
use QUI\MCP\Project\Sites\GetSiteByUrl;
use QUI\MCP\Project\Sites\ListSites;
use QUI\MCP\Project\Sites\MoveSite;
use QUI\MCP\Project\Sites\SearchSites;
use QUI\MCP\Project\Sites\SetSiteType;
use QUI\MCP\Project\Sites\SortSites;
use QUI\MCP\Project\Sites\UpdateSite;
use QUI\MCP\System\ClearCache;
use QUI\MCP\System\Update;
use QUI\Permissions\Permission;
use Throwable;

/**
 * Core MCP provider
 */
class Provider implements ProviderInterface
{
    /**
     * @var array<ToolInterface>
     */
    protected array $tools;

    public function __construct()
    {
        $this->tools = [
            new ListProjects(),
            new GetCustomCSS(),
            new SetCustomCSS(),
            new GetCustomJavaScript(),
            new SetCustomJavaScript(),
            new ListSites(),
            new GetSite(),
            new GetSiteByUrl(),
            new SearchSites(),
            new AddLanguageLink(),
            new CopySite(),
            new CopySiteToLanguage(),
            new CreateChild(),
            new UpdateSite(),
            new ActivateSite(),
            new DeactivateSite(),
            new MoveSite(),
            new SortSites(),
            new SetSiteType(),
            new DeleteSite(),
            new ClearCache(),
            new Update(),
            new GetMedia(),
            new ListMedia(),
            new SearchMedia(),
            new CreateFolder(),
            new UploadMedia(),
            new UpdateMedia(),
            new ActivateMedia(),
            new DeactivateMedia(),
            new DeleteMedia()
        ];
    }

    public function register(Builder $serverBuilder): void
    {
        if (!$this->canUseMcp()) {
            return;
        }

        foreach ($this->tools as $Tool) {
            $Tool->register($serverBuilder);
        }
    }

    protected function canUseMcp(): bool
    {
        try {
            Permission::checkPermission(
                'quiqqer.core.mcp.canUse',
                Server::getRequestUser()
            );

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
