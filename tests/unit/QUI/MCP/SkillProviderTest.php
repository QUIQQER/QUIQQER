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

    public function testFrontendSkillsAreRegistered(): void
    {
        if (!class_exists(SkillRepository::class)) {
            self::markTestSkipped('The quiqqer/ai-mcp skill repository is not installed.');
        }

        $Repository = new SkillRepository();
        $Provider = new SkillProvider();
        $Provider->registerSkills($Repository);

        $WorkflowSkill = $Repository->get('quiqqer_developer_workflow');
        $CssSkill = $Repository->get('quiqqer_frontend_css_variables');
        $JsSkill = $Repository->get('quiqqer_frontend_javascript');
        $A11ySkill = $Repository->get('quiqqer_frontend_accessibility');

        $this->assertNotNull($CssSkill);
        $this->assertNotNull($JsSkill);
        $this->assertNotNull($A11ySkill);
        $this->assertStringContainsString('quiqqer_frontend_css_variables', $WorkflowSkill->getContent());
        $this->assertStringContainsString('quiqqer_frontend_javascript', $WorkflowSkill->getContent());
        $this->assertStringContainsString('quiqqer_frontend_accessibility', $WorkflowSkill->getContent());
        $this->assertSame('developer', $CssSkill->getCategory()->value);
        $this->assertStringContainsString('--_q-controlConf-', $CssSkill->getContent());
        $this->assertStringContainsString('data-name', $JsSkill->getContent());
        $this->assertStringContainsString('aria-hidden', $A11ySkill->getContent());
    }

    public function testSecureCodingSkillIsRegistered(): void
    {
        if (!class_exists(SkillRepository::class)) {
            self::markTestSkipped('The quiqqer/ai-mcp skill repository is not installed.');
        }

        $Repository = new SkillRepository();
        $Provider = new SkillProvider();
        $Provider->registerSkills($Repository);

        $WorkflowSkill = $Repository->get('quiqqer_developer_workflow');
        $Skill = $Repository->get('quiqqer_secure_coding');

        $this->assertNotNull($Skill);
        $this->assertStringContainsString('quiqqer_secure_coding', $WorkflowSkill->getContent());
        $this->assertSame('developer', $Skill->getCategory()->value);
        $this->assertStringContainsString("|escape:'html'", $Skill->getContent());
        $this->assertStringContainsString('prepared statements', $Skill->getContent());
        $this->assertStringContainsString('parameter binding', $Skill->getContent());
    }
}
