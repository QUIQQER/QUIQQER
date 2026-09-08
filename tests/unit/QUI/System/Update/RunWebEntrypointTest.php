<?php

namespace QUI\System\Update;

use PHPUnit\Framework\TestCase;

class RunWebEntrypointTest extends TestCase
{
    public function testRunnerEscapesIdentifierForInlineScriptContext(): void
    {
        $root = dirname(__DIR__, 5);
        $entrypoint = (string)file_get_contents($root . '/bin/update-run.php');
        $attack = '</script><script>alert(1)</script>';
        $encodedAttack = json_encode(
            $attack,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        $this->assertStringContainsString(
            'json_encode($id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)',
            $entrypoint
        );
        $this->assertIsString($encodedAttack);
        $this->assertStringNotContainsString('</script>', $encodedAttack);
        $this->assertStringNotContainsString('<script>', $encodedAttack);
    }

    public function testStatusAndSseReadsRecheckTokenExpiration(): void
    {
        $root = dirname(__DIR__, 5);
        $entrypoint = (string)file_get_contents($root . '/bin/update-run.php');

        $this->assertSame(2, substr_count($entrypoint, '$State->assertAuthorized($token, time());'));
    }

    public function testRunnerDoesNotAcceptOrPropagateTokensInQueryStrings(): void
    {
        $root = dirname(__DIR__, 5);
        $entrypoint = (string)file_get_contents($root . '/bin/update-run.php');
        $adminControl = (string)file_get_contents($root . '/bin/QUI/controls/packages/System.js');

        $this->assertStringNotContainsString("\$_GET['token']", $entrypoint);
        $this->assertStringNotContainsString("searchParams.set('token'", $entrypoint);
        $this->assertStringContainsString("\$_POST['token']", $entrypoint);
        $this->assertStringContainsString("'httponly' => true", $entrypoint);
        $this->assertStringContainsString("'samesite' => 'Strict'", $entrypoint);
        $this->assertStringContainsString("Form.method = 'post'", $adminControl);
    }
}
