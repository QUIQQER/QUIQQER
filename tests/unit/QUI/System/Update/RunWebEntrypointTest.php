<?php

namespace QUI\System\Update;

use PHPUnit\Framework\TestCase;

class RunWebEntrypointTest extends TestCase
{
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
