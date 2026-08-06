<?php

namespace QUI\Permissions;

use QUI;
use QUI\Groups\Group;
use QUI\Projects\Project;

class AccessibleManager extends Manager
{
    public ?string $dispatchedArea = null;

    public function __construct()
    {
    }

    /**
     * @param array<string, array<string, mixed>> $cache
     */
    public function setTestCache(array $cache): void
    {
        $this->cache = $cache;
    }

    public function getTestObjectArea(mixed $Object): string
    {
        return $this->objectToArea($Object);
    }

    /**
     * @param array<array-key, mixed>|int|string $value
     * @return array<array-key, mixed>|bool|int|string
     */
    public function cleanTestValue(string $type, int|array|string $value): int|bool|array|string
    {
        return $this->cleanValue($type, $value);
    }

    public function setProjectPermissions(
        Project $Project,
        array $permissions,
        null|QUI\Interfaces\Users\User $EditUser = null
    ): void {
        $this->dispatchedArea = 'project';
    }

    public function setSitePermissions(
        QUI\Interfaces\Projects\Site $Site,
        array $permissions,
        null|QUI\Interfaces\Users\User $EditUser = null
    ): void {
        $this->dispatchedArea = 'site';
    }

    public function setMediaPermissions(
        QUI\Projects\Media\Item $MediaItem,
        array $permissions,
        null|QUI\Interfaces\Users\User $EditUser = null
    ): void {
        $this->dispatchedArea = 'media';
    }
}
