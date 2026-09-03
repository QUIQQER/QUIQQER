<?php

declare(strict_types=1);

namespace QUI\Upload;

use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function strpos;
use function substr;

final class ClientCsrfTokenTest extends TestCase
{
    public function testBulkUploadSendsCurrentBackendCsrfToken(): void
    {
        $source = $this->getSource('bin/QUI/classes/request/BulkUpload.js');
        $method = $this->getMethod($source, '$uploadFilePart: function', '$parseResult: function');

        self::assertStringContainsString(
            'function (QUI, QDOM, ObjectUtils, QUIMath, Ajax, QUILocale)',
            $source
        );
        self::assertStringContainsString(
            'UploadParams._csrf = Ajax.getBackendCsrfToken();',
            $method
        );
    }

    public function testUploadFileSendsCurrentBackendCsrfToken(): void
    {
        $source = $this->getSource('bin/QUI/controls/upload/File.js');
        $method = $this->getMethod($source, '$upload: function', '$parseResult: function');

        self::assertMatchesRegularExpression('/\bAjax\s*=\s*arguments\[10]/', $source);
        self::assertStringContainsString(
            'UploadParams._csrf = Ajax.getBackendCsrfToken();',
            $method
        );
    }

    public function testNonHtml5FormUploadSendsCurrentBackendCsrfToken(): void
    {
        $source = $this->getSource('bin/QUI/controls/upload/Form.js');
        $method = $this->getMethod($source, 'submit: function', 'finish: function');

        self::assertStringContainsString(
            'function (QUI, QUIControl, QUIProgressbar, QUIButton, QUILoader, MediaUtils, Upload, Ajax, Locale)',
            $source
        );
        self::assertStringContainsString(
            'this.$params._csrf = Ajax.getBackendCsrfToken();',
            $method
        );
    }

    private function getSource(string $path): string
    {
        return (string)file_get_contents(dirname(__DIR__, 4) . '/' . $path);
    }

    private function getMethod(string $source, string $startMarker, string $endMarker): string
    {
        $methodStart = strpos($source, $startMarker);

        self::assertIsInt($methodStart);

        $methodEnd = strpos($source, $endMarker, $methodStart);

        self::assertIsInt($methodEnd);

        return substr($source, $methodStart, $methodEnd - $methodStart);
    }
}
