<?php

declare(strict_types=1);

namespace QUI\Mail;

use PHPUnit\Framework\TestCase;

class UserMailPlaceholdersTest extends TestCase
{
    public function testReplacesAllUserPlaceholders(): void
    {
        $sut = new UserMailPlaceholders(
            [
                'uuid' => '5fd49538-4a69-4a6b-9b4d-758b01b9da1e',
                'id' => 42,
                'email' => 'jane@example.com',
                'username' => 'jane.doe'
            ],
            [
                'salutation' => 'Frau',
                'firstname' => 'Jane',
                'lastname' => 'Doe',
                'street_no' => 'Musterstraße 1',
                'city' => 'Berlin',
                'company' => 'Example GmbH',
                'zip' => '10115'
            ],
            'Deutschland'
        );

        $content = '[user_uuid]|[user_id]|[user_salutation]|[user_firstname]|[user_lastname]|'
            . '[user_street_no]|[user_city]|[user_country]|[user_email]|[user_company]|[user_zip]|[user_username]';

        self::assertSame(
            '5fd49538-4a69-4a6b-9b4d-758b01b9da1e|42|Frau|Jane|Doe|Musterstraße 1|Berlin|Deutschland|'
            . 'jane@example.com|Example GmbH|10115|jane.doe',
            $sut->replace($content)
        );
    }

    public function testReplacesMissingValuesWithEmptyStrings(): void
    {
        $sut = new UserMailPlaceholders([], [], '');

        self::assertSame(
            '||||||||||||[group_title]',
            $sut->replace(
                '[user_uuid]|[user_id]|[user_salutation]|[user_firstname]|[user_lastname]|[user_street_no]|'
                . '[user_city]|[user_country]|[user_email]|[user_company]|[user_zip]|[user_username]|[group_title]'
            )
        );
    }

    public function testReplacesExplicitAdditionalPlaceholders(): void
    {
        $sut = new UserMailPlaceholders([], [], '');

        self::assertSame(
            'Newsletter',
            $sut->replace('[group_title]', ['[group_title]' => 'Newsletter'])
        );
    }
}
