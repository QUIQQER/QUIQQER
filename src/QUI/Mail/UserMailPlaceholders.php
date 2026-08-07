<?php

declare(strict_types=1);

namespace QUI\Mail;

/**
 * Replaces user-related placeholders in emails.
 */
final class UserMailPlaceholders
{
    /**
     * @var array<string, string>
     */
    private readonly array $placeholders;

    /**
     * @param array<string, mixed> $user
     * @param array<string, mixed> $address
     */
    public function __construct(array $user, array $address, string $countryName)
    {
        $this->placeholders = [
            '[user_uuid]' => (string)($user['uuid'] ?? ''),
            '[user_id]' => (string)($user['id'] ?? ''),
            '[user_salutation]' => (string)($address['salutation'] ?? ''),
            '[user_firstname]' => (string)($address['firstname'] ?? ''),
            '[user_lastname]' => (string)($address['lastname'] ?? ''),
            '[user_street_no]' => (string)($address['street_no'] ?? ''),
            '[user_city]' => (string)($address['city'] ?? ''),
            '[user_country]' => $countryName,
            '[user_email]' => (string)($user['email'] ?? ''),
            '[user_company]' => (string)($address['company'] ?? ''),
            '[user_zip]' => (string)($address['zip'] ?? ''),
            '[user_username]' => (string)($user['username'] ?? '')
        ];
    }

    /**
     * @param array<string, string> $additionalPlaceholders
     */
    public function replace(string $content, array $additionalPlaceholders = []): string
    {
        return strtr($content, [...$this->placeholders, ...$additionalPlaceholders]);
    }
}
