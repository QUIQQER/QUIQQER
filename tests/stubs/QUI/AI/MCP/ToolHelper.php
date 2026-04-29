<?php

namespace QUI\AI\MCP;

use Mcp\Schema\Result\CallToolResult;

if (!class_exists(ToolHelper::class)) {
    class ToolHelper
    {
        public static function parseExceptionToResult(mixed $e): CallToolResult
        {
            return new CallToolResult();
        }
    }
}
