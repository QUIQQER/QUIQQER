<?php

namespace QUI\AI\MCP;

use QUI;

if (!class_exists(Server::class)) {
    class Server
    {
        public static function getRequestUser(): QUI\Interfaces\Users\User
        {
            return new QUI\Users\Nobody();
        }
    }
}
