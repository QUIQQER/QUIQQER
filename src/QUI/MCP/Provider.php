<?php

/**
 * This file contains the \QUI\MCP\Provider
 */

namespace QUI\MCP;

use Mcp\Server\Builder;
use QUI\AI\MCP\ProviderInterface;
use QUI\AI\MCP\Server;
use QUI\MCP\Groups\ActivateGroup;
use QUI\MCP\Groups\AddGroupUsers;
use QUI\MCP\Groups\CreateGroup;
use QUI\MCP\Groups\DeactivateGroup;
use QUI\MCP\Groups\DeleteGroup;
use QUI\MCP\Groups\GetGroup;
use QUI\MCP\Groups\ListGroups;
use QUI\MCP\Groups\ListGroupUsers;
use QUI\MCP\Groups\ListUserGroups;
use QUI\MCP\Groups\RemoveGroupUsers;
use QUI\MCP\Groups\SearchGroups;
use QUI\MCP\Groups\UpdateGroup;
use QUI\MCP\Project\AddLanguage;
use QUI\MCP\Project\GetCustomCSS;
use QUI\MCP\Project\GetCustomJavaScript;
use QUI\MCP\Project\GetSetting;
use QUI\MCP\Project\ListProjects;
use QUI\MCP\Project\ListSettings;
use QUI\MCP\Project\Media\ActivateMedia;
use QUI\MCP\Project\Media\CreateUploadSession;
use QUI\MCP\Project\Media\CreateFolder;
use QUI\MCP\Project\Media\DeactivateMedia;
use QUI\MCP\Project\Media\DeleteMedia;
use QUI\MCP\Project\Media\FinalizeUpload;
use QUI\MCP\Project\Media\GetMedia;
use QUI\MCP\Project\Media\GetUploadSession;
use QUI\MCP\Project\Media\ListMedia;
use QUI\MCP\Project\Media\SearchMedia;
use QUI\MCP\Project\Media\UpdateMedia;
use QUI\MCP\Project\Media\UploadMedia;
use QUI\MCP\Project\SetCustomCSS;
use QUI\MCP\Project\SetCustomJavaScript;
use QUI\MCP\Project\SetSetting;
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
use QUI\MCP\Project\UpdateSettings;
use QUI\MCP\System\ClearCache;
use QUI\MCP\System\GetSystemInfo;
use QUI\MCP\System\Update;
use QUI\MCP\Users\ActivateUser;
use QUI\MCP\Users\CreateUser;
use QUI\MCP\Users\DeactivateUser;
use QUI\MCP\Users\DeleteUser;
use QUI\MCP\Users\GetUser;
use QUI\MCP\Users\ListUsers;
use QUI\MCP\Users\SearchUsers;
use QUI\MCP\Users\UpdateUser;
use QUI\MCP\VHost\CreateVHost;
use QUI\MCP\VHost\DeleteVHost;
use QUI\MCP\VHost\GetVHost;
use QUI\MCP\VHost\ListVHosts;
use QUI\MCP\VHost\UpdateVHost;
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
            new AddLanguage(),
            new ListSettings(),
            new GetSetting(),
            new SetSetting(),
            new UpdateSettings(),
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
            new GetSystemInfo(),
            new ClearCache(),
            new Update(),
            new ListVHosts(),
            new GetVHost(),
            new CreateVHost(),
            new UpdateVHost(),
            new DeleteVHost(),
            new GetMedia(),
            new ListMedia(),
            new SearchMedia(),
            new CreateFolder(),
            new UploadMedia(),
            new CreateUploadSession(),
            new GetUploadSession(),
            new FinalizeUpload(),
            new UpdateMedia(),
            new ActivateMedia(),
            new DeactivateMedia(),
            new DeleteMedia(),
            new ListUsers(),
            new SearchUsers(),
            new GetUser(),
            new CreateUser(),
            new UpdateUser(),
            new ActivateUser(),
            new DeactivateUser(),
            new DeleteUser(),
            new ListGroups(),
            new SearchGroups(),
            new GetGroup(),
            new CreateGroup(),
            new UpdateGroup(),
            new ActivateGroup(),
            new DeactivateGroup(),
            new DeleteGroup(),
            new ListUserGroups(),
            new ListGroupUsers(),
            new AddGroupUsers(),
            new RemoveGroupUsers()
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
