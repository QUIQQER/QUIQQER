<?php

declare(strict_types=1);

namespace QUI\Mail;

use PHPUnit\Framework\TestCase;

use function file_get_contents;

final class MailSettingsTransportSecurityTest extends TestCase
{
    public function testSmtpPasswordIsSentInPostBody(): void
    {
        $source = (string)file_get_contents(
            dirname(__DIR__, 4) . '/bin/QUI/controls/system/settings/Mail.js'
        );

        self::assertStringContainsString(
            "QUIAjax.post('ajax_system_mailTest'",
            $source
        );
        self::assertStringNotContainsString(
            "QUIAjax.get('ajax_system_mailTest'",
            $source
        );
    }
}
