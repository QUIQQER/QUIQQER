<?php

namespace QUI\MCP\Project\Sites;

use QUI;
use QUI\AI\MCP\Server;
use QUI\Lock\Locker;
use QUI\MCP\AbstractTool;
use QUI\Projects\Site\Edit;

abstract class AbstractSiteAdministrationTool extends AbstractTool
{
    protected static function getManagedSite(
        string $project,
        int $siteId,
        ?string $lang,
        string $permission
    ): Edit {
        self::checkCorePermission();
        $Site = self::getEditSite($project, $siteId, $lang);
        $Site->checkPermission($permission, Server::getRequestUser());

        return $Site;
    }

    protected static function getLockKey(Edit $Site): string
    {
        $Project = $Site->getProject();

        return $Project->getName() . '_' . $Project->getLang() . '_' . $Site->getId();
    }

    protected static function getLockOwner(Edit $Site): mixed
    {
        return Locker::isLocked(
            QUI::getPackage('quiqqer/core'),
            self::getLockKey($Site),
            Server::getRequestUser(),
            false
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getLockResponse(Edit $Site, mixed $owner = null): array
    {
        if ($owner === null) {
            $owner = self::getLockOwner($Site);
        }

        $requestUserId = (string)Server::getRequestUser()->getUUID();
        $ownerId = $owner === false ? null : (string)$owner;
        $ownerData = null;

        if ($ownerId !== null && $ownerId !== '') {
            try {
                $Owner = QUI::getUsers()->get($ownerId);
                $ownerData = [
                    'uuid' => $Owner->getUUID(),
                    'username' => $Owner->getUsername(),
                    'displayName' => $Owner->getName()
                ];
            } catch (QUI\Exception) {
                $ownerData = ['uuid' => $ownerId];
            }
        }

        return [
            'site' => self::parseSite($Site),
            'locked' => $ownerId !== null && $ownerId !== '',
            'ownedByRequestUser' => $ownerId !== null && $ownerId === $requestUserId,
            'owner' => $ownerData
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getSiteIdSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['project', 'id'],
            'properties' => [
                'project' => ['type' => 'string', 'minLength' => 1],
                'id' => ['type' => 'integer', 'minimum' => 1],
                'lang' => ['type' => ['string', 'null'], 'pattern' => '^[a-z]{2}$']
            ]
        ];
    }
}
