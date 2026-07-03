<?php

namespace QUI\AI\MCP\Skill;

if (!interface_exists(SkillProviderInterface::class)) {
    interface SkillProviderInterface
    {
        public function registerSkills(SkillRepository $repository): void;
    }
}
