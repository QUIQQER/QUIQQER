<?php

namespace QUITests\Fixtures\FrontendEntrypoint;

use stdClass;

final class QUI
{
    public static function getGlobalResponse(): object
    {
        return new stdClass();
    }

    public static function getRequest(): object
    {
        return new stdClass();
    }

    public static function getProjectManager(): ProjectManager
    {
        return new ProjectManager();
    }

    public static function getRewrite(): Rewrite
    {
        return new Rewrite();
    }
}
