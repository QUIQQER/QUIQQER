<?php

if (!defined('QUIQQER_SYSTEM')) {
    define('QUIQQER_SYSTEM', true);
}

if (!defined('QUIQQER_AJAX')) {
    define('QUIQQER_AJAX', true);
}

putenv("QUIQQER_OTHER_AUTOLOADERS=KEEP");

require_once __DIR__ . '/../minimalBootstrap.php';
require_once __DIR__ . '/stubs/Mcp/Server/Builder.php';
require_once __DIR__ . '/stubs/Mcp/Schema/Result/CallToolResult.php';
require_once __DIR__ . '/stubs/QUI/AI/MCP/ProviderInterface.php';
require_once __DIR__ . '/stubs/QUI/AI/MCP/Server.php';
require_once __DIR__ . '/stubs/QUI/AI/MCP/ToolHelper.php';
require_once __DIR__ . '/stubs/QUI/AI/MCP/Skill/SkillProviderInterface.php';
require_once __DIR__ . '/stubs/QUI/AI/MCP/Skill/SkillRepository.php';
require_once __DIR__ . '/stubs/QUI/FrontendUsers/Exception.php';
require_once __DIR__ . '/stubs/QUI/FrontendUsers/AbstractRegistrar.php';
