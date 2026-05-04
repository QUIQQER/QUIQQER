<?php

namespace QUI\AI\MCP;

use Mcp\Server\Builder;

if (!interface_exists(ProviderInterface::class)) {
    interface ProviderInterface
    {
        public function register(Builder $serverBuilder): void;
    }
}
