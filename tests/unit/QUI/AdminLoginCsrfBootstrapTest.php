<?php

declare(strict_types=1);

namespace QUI;

use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function strpos;
use function substr;

final class AdminLoginCsrfBootstrapTest extends TestCase
{
    public function testAdminLoginBootstrapsCsrfTokenStorage(): void
    {
        $source = (string)file_get_contents(
            dirname(__DIR__, 3) . '/admin/login.php'
        );
        $scriptStart = strpos($source, "echo '<script type=\"text/javascript\">';");

        self::assertIsInt($scriptStart);

        $scriptEnd = strpos($source, "echo '</script>';", $scriptStart);

        self::assertIsInt($scriptEnd);

        $bootstrap = substr($source, $scriptStart, $scriptEnd - $scriptStart);

        self::assertStringContainsString("echo 'var QUIQQER = '", $bootstrap);
        self::assertStringContainsString("'csrfToken' => QUI\\Security\\CsrfToken::get()", $bootstrap);
        self::assertStringContainsString("'inAdministration' => true", $bootstrap);
    }
}
