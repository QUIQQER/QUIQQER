<?php

namespace QUI\AI\MCP;

use QUI;

if (!class_exists(Server::class)) {
    class Server
    {
        protected static ?QUI\Interfaces\Users\User $RequestUser = null;

        public static function getRequestUser(): QUI\Interfaces\Users\User
        {
            return self::$RequestUser ?? new QUI\Users\Nobody();
        }
    }
}
