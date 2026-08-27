<?php

/**
 * This file contains the \QUI\MCP\Provider
 */

namespace QUI\MCP;

use Mcp\Server\Builder;
use QUI\AI\MCP\ProviderInterface;
use QUI\AI\MCP\Server;
use QUI\MCP\Forwarding\CreateForwarding;
use QUI\MCP\Forwarding\DeleteForwardings;
use QUI\MCP\Forwarding\GetForwarding;
use QUI\MCP\Forwarding\ListForwardings;
use QUI\MCP\Forwarding\UpdateForwarding;
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
use QUI\MCP\Permissions\GetEffectivePermission;
use QUI\MCP\Permissions\GetGroupPermissions;
use QUI\MCP\Permissions\GetMediaPermissions;
use QUI\MCP\Permissions\GetProjectPermissions;
use QUI\MCP\Permissions\GetSitePermissions;
use QUI\MCP\Permissions\GetUserPermissions;
use QUI\MCP\Permissions\ListPermissions;
use QUI\MCP\Permissions\UpdateGroupPermissions;
use QUI\MCP\Permissions\UpdateMediaPermissions;
use QUI\MCP\Permissions\UpdateProjectPermissions;
use QUI\MCP\Permissions\UpdateSitePermissions;
use QUI\MCP\Permissions\UpdateUserPermissions;
use QUI\MCP\Project\CreateDefaultStructure;
use QUI\MCP\Project\CreateProject;
use QUI\MCP\Project\DeleteProject;
use QUI\MCP\Project\AddLanguage;
use QUI\MCP\Project\GetCustomCSS;
use QUI\MCP\Project\GetCustomJavaScript;
use QUI\MCP\Project\GetProject;
use QUI\MCP\Project\GetSetting;
use QUI\MCP\Project\ListAvailableLanguages;
use QUI\MCP\Project\ListDemoDataSets;
use QUI\MCP\Project\ListProjects;
use QUI\MCP\Project\ListProjectTemplates;
use QUI\MCP\Project\ListSettings;
use QUI\MCP\Project\Media\ActivateMedia;
use QUI\MCP\Project\Media\CreateUploadSession;
use QUI\MCP\Project\Media\CreateFolder;
use QUI\MCP\Project\Media\CopyMedia;
use QUI\MCP\Project\Media\DeactivateMedia;
use QUI\MCP\Project\Media\DeleteMedia;
use QUI\MCP\Project\Media\DownloadMedia;
use QUI\MCP\Project\Media\DownloadMediaFolder;
use QUI\MCP\Project\Media\FinalizeUpload;
use QUI\MCP\Project\Media\GetMedia;
use QUI\MCP\Project\Media\GetMediaFolderSize;
use QUI\MCP\Project\Media\GetUploadSession;
use QUI\MCP\Project\Media\ListMedia;
use QUI\MCP\Project\Media\MoveMedia;
use QUI\MCP\Project\Media\RenameMedia;
use QUI\MCP\Project\Media\ReplaceMedia;
use QUI\MCP\Project\Media\SearchMedia;
use QUI\MCP\Project\Media\UpdateMedia;
use QUI\MCP\Project\Media\UpdateMediaVisibility;
use QUI\MCP\Project\Media\UploadMedia;
use QUI\MCP\Project\SetCustomCSS;
use QUI\MCP\Project\SetCustomJavaScript;
use QUI\MCP\Project\SetSetting;
use QUI\MCP\Project\Sites\ActivateSite;
use QUI\MCP\Project\Sites\AddLanguageLink;
use QUI\MCP\Project\Sites\ClearSiteCache;
use QUI\MCP\Project\Sites\CopySite;
use QUI\MCP\Project\Sites\CopySiteToLanguage;
use QUI\MCP\Project\Sites\CreateChild;
use QUI\MCP\Project\Sites\CreateSiteCache;
use QUI\MCP\Project\Sites\DeactivateSite;
use QUI\MCP\Project\Sites\DeleteSite;
use QUI\MCP\Project\Sites\GetSite;
use QUI\MCP\Project\Sites\GetSiteByUrl;
use QUI\MCP\Project\Sites\GetSiteLock;
use QUI\MCP\Project\Sites\LinkSite;
use QUI\MCP\Project\Sites\ListSiteLayouts;
use QUI\MCP\Project\Sites\ListSites;
use QUI\MCP\Project\Sites\ListSiteTypes;
use QUI\MCP\Project\Sites\LockSite;
use QUI\MCP\Project\Sites\MoveSite;
use QUI\MCP\Project\Sites\RemoveLanguageLink;
use QUI\MCP\Project\Sites\SearchSites;
use QUI\MCP\Project\Sites\SetSiteType;
use QUI\MCP\Project\Sites\SortSites;
use QUI\MCP\Project\Sites\UnlinkSite;
use QUI\MCP\Project\Sites\UnlockSite;
use QUI\MCP\Project\Sites\UpdateSite;
use QUI\MCP\Project\Trash\ClearMediaTrash;
use QUI\MCP\Project\Trash\ClearSiteTrash;
use QUI\MCP\Project\Trash\DestroyMedia;
use QUI\MCP\Project\Trash\DestroySites;
use QUI\MCP\Project\Trash\ListMediaTrash;
use QUI\MCP\Project\Trash\ListSiteTrash;
use QUI\MCP\Project\Trash\RestoreMedia;
use QUI\MCP\Project\Trash\RestoreSites;
use QUI\MCP\Project\RenameProject;
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
            new GetProject(),
            new CreateProject(),
            new RenameProject(),
            new DeleteProject(),
            new CreateDefaultStructure(),
            new ListAvailableLanguages(),
            new ListProjectTemplates(),
            new ListDemoDataSets(),
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
            new RemoveLanguageLink(),
            new LinkSite(),
            new UnlinkSite(),
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
            new GetSiteLock(),
            new LockSite(),
            new UnlockSite(),
            new ListSiteTypes(),
            new ListSiteLayouts(),
            new ClearSiteCache(),
            new CreateSiteCache(),
            new GetSystemInfo(),
            new ClearCache(),
            new Update(),
            new ListVHosts(),
            new GetVHost(),
            new CreateVHost(),
            new UpdateVHost(),
            new DeleteVHost(),
            new ListForwardings(),
            new GetForwarding(),
            new CreateForwarding(),
            new UpdateForwarding(),
            new DeleteForwardings(),
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
            new MoveMedia(),
            new CopyMedia(),
            new RenameMedia(),
            new ReplaceMedia(),
            new UpdateMediaVisibility(),
            new DownloadMedia(),
            new DownloadMediaFolder(),
            new GetMediaFolderSize(),
            new ListSiteTrash(),
            new RestoreSites(),
            new DestroySites(),
            new ClearSiteTrash(),
            new ListMediaTrash(),
            new RestoreMedia(),
            new DestroyMedia(),
            new ClearMediaTrash(),
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
            new RemoveGroupUsers(),
            new ListPermissions(),
            new GetUserPermissions(),
            new UpdateUserPermissions(),
            new GetGroupPermissions(),
            new UpdateGroupPermissions(),
            new GetProjectPermissions(),
            new UpdateProjectPermissions(),
            new GetSitePermissions(),
            new UpdateSitePermissions(),
            new GetMediaPermissions(),
            new UpdateMediaPermissions(),
            new GetEffectivePermission()
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
