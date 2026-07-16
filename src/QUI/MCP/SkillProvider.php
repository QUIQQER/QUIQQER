<?php

/**
 * This file contains the \QUI\MCP\SkillProvider
 */

namespace QUI\MCP;

use QUI\AI\MCP\Skill\SkillProviderInterface;
use QUI\AI\MCP\Skill\SkillRepository;

/**
 * Core MCP skill provider
 */
class SkillProvider implements SkillProviderInterface
{
    public function registerSkills(SkillRepository $repository): void
    {
        $root = dirname(__DIR__, 3);

        $repository->addFromMarkdownFile(
            $root . '/skills/developer/quiqqer_developer_workflow.md'
        );

        $repository->addFromMarkdownFile(
            $root . '/skills/developer/quiqqer_extension_points.md'
        );

        $repository->addFromMarkdownFile(
            $root . '/skills/developer/quiqqer_package_quality_upgrade.md'
        );

        $repository->addFromMarkdownFile(
            $root . '/skills/developer/quiqqer_frontend_css_variables.md'
        );

        $repository->addFromMarkdownFile(
            $root . '/skills/developer/quiqqer_frontend_javascript.md'
        );

        $repository->addFromMarkdownFile(
            $root . '/skills/developer/quiqqer_frontend_accessibility.md'
        );

        $repository->addFromMarkdownFile(
            $root . '/skills/developer/quiqqer_secure_coding.md'
        );
    }
}
