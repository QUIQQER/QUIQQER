<?php

namespace QUI\MCP;

use PHPUnit\Framework\TestCase;
use QUI\AI\MCP\Skill\SkillRepository;

class SkillProviderTest extends TestCase
{
    public function testPackageQualityUpgradeSkillIsRegistered(): void
    {
        if (!class_exists(SkillRepository::class)) {
            self::markTestSkipped('The quiqqer/ai-mcp skill repository is not installed.');
        }

        $Repository = new SkillRepository();
        $Provider = new SkillProvider();
        $Provider->registerSkills($Repository);

        $WorkflowSkill = $Repository->get('quiqqer_developer_workflow');
        $Skill = $Repository->get('quiqqer_package_quality_upgrade');

        $this->assertNotNull($WorkflowSkill);
        $this->assertNotNull($Skill);
        $this->assertStringContainsString('quiqqer_package_quality_upgrade', $WorkflowSkill->getContent());
        $this->assertSame('developer', $Skill->getCategory()->value);
        $this->assertStringContainsString('PHPStan 2 at level 8', $Skill->getDescription());
        $this->assertStringContainsString('Do not switch branches', $Skill->getContent());
        $this->assertStringContainsString('min: 80200', $Skill->getContent());
        $this->assertStringContainsString('max: 80509', $Skill->getContent());
        $this->assertStringContainsString('QUI::getDataBaseConnection()', $Skill->getContent());
        $this->assertStringContainsString('./tools/phpunit', $Skill->getContent());
    }
}
