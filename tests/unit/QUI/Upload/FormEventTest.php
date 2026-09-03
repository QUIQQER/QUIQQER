<?php

declare(strict_types=1);

namespace QUI\Upload;

use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function strpos;
use function substr;

final class FormEventTest extends TestCase
{
    public function testBulkUploadFinishEventsUseTheFormInstance(): void
    {
        $source = (string)file_get_contents(
            dirname(__DIR__, 4) . '/bin/QUI/controls/upload/Form.js'
        );
        $submitStart = strpos($source, 'submit: function');

        self::assertIsInt($submitStart);

        $submitEnd = strpos($source, 'finish: function', $submitStart);

        self::assertIsInt($submitEnd);

        $submit = substr($source, $submitStart, $submitEnd - $submitStart);

        self::assertStringContainsString(
            'const startUpload = (preparedFiles) => {',
            $submit
        );
        self::assertStringContainsString(
            "this.fireEvent('finished', [this, uploadedFiles, Instance]);",
            $submit
        );
        self::assertStringContainsString(
            "this.fireEvent('complete', [this, uploadedFiles, Instance]);",
            $submit
        );
    }
}
