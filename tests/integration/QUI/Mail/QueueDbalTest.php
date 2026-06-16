<?php

namespace QUI\Mail;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use QUI;
use Throwable;

class QueueDbalTest extends TestCase
{
    private const TEST_SUBJECT_PREFIX = 'codex-mailqueue-from-column-';

    public static function setUpBeforeClass(): void
    {
        self::skipIfDatabaseIsUnavailable();
        self::cleanupTestMails();
    }

    protected function tearDown(): void
    {
        self::cleanupTestMails();
    }

    public static function tearDownAfterClass(): void
    {
        self::cleanupTestMails();
    }

    public function testAddToQueueQuotesReservedFromColumn(): void
    {
        self::skipIfDatabaseIsUnavailable();

        $subject = self::TEST_SUBJECT_PREFIX . uniqid('', true);
        $Mailer = new Mailer();
        $Mailer->setSubject($subject);
        $Mailer->setFrom('ci@quiqqer.com');
        $Mailer->setFromName('PHPUnit Sender');
        $Mailer->addRecipient('ci@quiqqer.com');
        $Mailer->setBody('<p>Reserved from column regression test</p>');

        $mailId = Queue::addToQueue($Mailer);

        $Connection = self::getConnection();
        $Platform = $Connection->getDatabasePlatform();
        $entry = $Connection->createQueryBuilder()
            ->select(
                $Platform->quoteSingleIdentifier('id'),
                $Platform->quoteSingleIdentifier('subject'),
                $Platform->quoteSingleIdentifier('from')
            )
            ->from(QUI\Utils\Doctrine::quoteIdentifier(Queue::table()))
            ->where($Platform->quoteSingleIdentifier('id') . ' = :id')
            ->setParameter('id', $mailId)
            ->executeQuery()
            ->fetchAssociative();

        $this->assertIsArray($entry);
        $this->assertSame($subject, $entry['subject']);
        $this->assertSame('ci@quiqqer.com', $entry['from']);
    }

    private static function skipIfDatabaseIsUnavailable(): void
    {
        try {
            Queue::setup();
            self::getConnection()->executeQuery(
                'SELECT 1 FROM ' . QUI\Utils\Doctrine::quoteIdentifier(Queue::table()) . ' LIMIT 1'
            )->free();
        } catch (Throwable $Exception) {
            self::markTestSkipped('QUIQQER database is not available: ' . $Exception->getMessage());
        }
    }

    private static function getConnection(): Connection
    {
        return QUI::getDataBaseConnection();
    }

    private static function cleanupTestMails(): void
    {
        try {
            $Connection = self::getConnection();
            $Platform = $Connection->getDatabasePlatform();

            $Connection->createQueryBuilder()
                ->delete(QUI\Utils\Doctrine::quoteIdentifier(Queue::table()))
                ->where($Platform->quoteSingleIdentifier('subject') . ' LIKE :subject')
                ->setParameter('subject', self::TEST_SUBJECT_PREFIX . '%')
                ->executeStatement();
        } catch (Throwable) {
            // The availability check reports DB problems. Cleanup should not hide the test result.
        }
    }
}
