<?php

namespace QUI\Permissions;

use QUI;
use QUI\Groups\Group;
use QUI\Projects\Media\Item;
use QUI\Projects\Project;

class SqliteAccessibleManager extends Manager
{
    protected function objectToArea(mixed $Object): string
    {
        if ($Object instanceof QUI\Interfaces\Users\User) {
            return 'user';
        }

        if ($Object instanceof Group) {
            return 'groups';
        }

        if ($Object instanceof Project) {
            return 'project';
        }

        if ($Object instanceof QUI\Interfaces\Projects\Site) {
            return 'site';
        }

        if ($Object instanceof Item) {
            return 'media';
        }

        return parent::objectToArea($Object);
    }

    public function getTestDataCacheId(mixed $Object): string
    {
        return $this->getDataCacheId($Object);
    }
}
