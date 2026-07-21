<?php

namespace QUI\System;

use Monolog\Handler\TestHandler;
use Monolog\Logger as MonologLogger;
use PHPUnit\Framework\TestCase;
use QUI\Log\Logger;
use QUI\Log\Monolog\QuiqqerMetadataProcessor;

class LogTest extends TestCase
{
    public function testFilenameIsPassedAsMonologMetadata(): void
    {
        $OriginalLogger = Logger::getLogger();
        $Handler = new TestHandler();
        $Logger = new MonologLogger('test', [$Handler]);
        $Logger->pushProcessor(new QuiqqerMetadataProcessor());
        Logger::$Logger = $Logger;

        try {
            Log::write('Login failed', Log::LEVEL_ERROR, filename: 'auth', force: true);
            $record = $Handler->getRecords()[0];

            self::assertSame(
                [
                    'contextFilenameExists' => false,
                    'extraFilename' => 'auth'
                ],
                [
                    'contextFilenameExists' => array_key_exists('filename', $record->context),
                    'extraFilename' => $record->extra['quiqqer']['filename']
                ]
            );
        } finally {
            Logger::$Logger = $OriginalLogger;
        }
    }

    public function testWriteExceptionUsesStructuredContextWithoutDuplicatingTheMessage(): void
    {
        $OriginalLogger = Logger::getLogger();
        $Handler = new TestHandler();
        $Logger = new MonologLogger('test', [$Handler]);
        Logger::$Logger = $Logger;

        $Exception = new \QUI\Exception('Something failed', 42, ['operation' => 'test']);

        try {
            Log::writeException($Exception, Log::LEVEL_ERROR, force: true);
            $record = $Handler->getRecords()[0];

            self::assertSame(
                [
                    'message' => 'Something failed',
                    'exception' => $Exception,
                    'exceptionContext' => ['operation' => 'test']
                ],
                [
                    'message' => $record->message,
                    'exception' => $record->context['exception'],
                    'exceptionContext' => $record->context['exceptionContext']
                ]
            );
        } finally {
            Logger::$Logger = $OriginalLogger;
        }
    }
}
