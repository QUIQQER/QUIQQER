<?php

/**
 * This file contains the \QUI\MCP\ToolInterface
 */

namespace QUI\MCP;

use Mcp\Server\Builder;

interface ToolInterface
{
    public function register(Builder $serverBuilder): void;
}
