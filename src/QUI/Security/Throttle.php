<?php

namespace QUI\Security;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use QUI;
use QUI\Interfaces\Users\User;

use function bin2hex;
use function hash;
use function random_bytes;
use function time;
use function trim;

/**
 * Persistent user and IP throttling for security-sensitive actions.
 */
final class Throttle
{
    /**
     * Atomically acquire a throttle window for a user action.
     */
    public static function acquireForUser(
        User $User,
        string $package,
        string $action,
        int $cooldownSeconds
    ): ThrottleDecision {
        $userUuid = trim((string)$User->getUUID());
        $package = trim($package);
        $action = trim($action);

        if ($userUuid === '') {
            throw new \InvalidArgumentException('A user UUID is required for throttling.');
        }

        if ($package === '') {
            throw new \InvalidArgumentException('A package name is required for throttling.');
        }

        if ($action === '') {
            throw new \InvalidArgumentException('An action name is required for throttling.');
        }

        if ($cooldownSeconds < 1) {
            throw new \InvalidArgumentException('The throttle cooldown must be greater than zero.');
        }

        $subjectKey = self::createHash("user\0" . $userUuid);
        $throttleKey = self::createHash($subjectKey . "\0" . $package . "\0" . $action);

        while (true) {
            $now = time();
            $retryAt = $now + $cooldownSeconds;
            $reservationId = bin2hex(random_bytes(16));
            $Connection = QUI::getDataBaseConnection();
            $table = QUI\Utils\Doctrine::quoteIdentifier(self::table());

            $updated = $Connection->executeStatement(
                'UPDATE ' . $table
                . ' SET package = ?, subjectKey = ?, reservationId = ?, expiresAt = ?'
                . ' WHERE throttleKey = ? AND expiresAt <= ?',
                [$package, $subjectKey, $reservationId, $retryAt, $throttleKey, $now]
            );

            if ($updated === 1) {
                return ThrottleDecision::allowed($throttleKey, $reservationId, $retryAt);
            }

            try {
                $Connection->insert($table, [
                    'throttleKey' => $throttleKey,
                    'package' => $package,
                    'subjectKey' => $subjectKey,
                    'reservationId' => $reservationId,
                    'expiresAt' => $retryAt
                ]);

                return ThrottleDecision::allowed($throttleKey, $reservationId, $retryAt);
            } catch (UniqueConstraintViolationException) {
                $currentRetryAt = $Connection->createQueryBuilder()
                    ->select('expiresAt')
                    ->from($table)
                    ->where('throttleKey = :throttleKey')
                    ->setParameter('throttleKey', $throttleKey)
                    ->executeQuery()
                    ->fetchOne();

                // The reservation may have been released between INSERT and SELECT.
                if ($currentRetryAt === false) {
                    continue;
                }

                return ThrottleDecision::denied((int)$currentRetryAt);
            }
        }
    }

    /**
     * Consume one request in a fixed, persistent IP window.
     *
     * Reservations are not released on success or failure. Sessions, account names
     * and equivalent textual IP representations cannot reset the shared budget.
     */
    public static function acquireForIp(
        string $ip,
        string $package,
        string $action,
        int $limit,
        int $windowSeconds
    ): bool {
        $package = trim($package);
        $action = trim($action);
        $address = filter_var($ip, FILTER_VALIDATE_IP) ? inet_pton($ip) : false;

        if ($address === false || $package === '' || $action === '' || $limit < 1 || $windowSeconds < 1) {
            throw new \InvalidArgumentException('A valid IP, package, action, limit and window are required.');
        }

        // IPv4-mapped IPv6 is the same source as its ordinary IPv4 representation.
        if (strlen($address) === 16 && substr($address, 0, 12) === str_repeat("\0", 10) . "\xff\xff") {
            $address = substr($address, 12);
        }

        $subjectKey = self::createHash("ip\0" . $address);
        $throttleKey = self::createHash($subjectKey . "\0" . $package . "\0" . $action);
        $Connection = QUI::getDataBaseConnection();
        $Platform = $Connection->getDatabasePlatform();
        $table = $Platform->quoteIdentifier(self::table());
        $attempts = $Platform->quoteIdentifier('attempts');
        $expiresAt = $Platform->quoteIdentifier('expiresAt');
        $now = time();
        $Update = $Connection->createQueryBuilder()
            ->update($table)
            ->set($attempts, "CASE WHEN $expiresAt <= :now THEN 1 ELSE $attempts + 1 END")
            ->set($expiresAt, "CASE WHEN $expiresAt <= :now THEN :expiresAt ELSE $expiresAt END")
            ->where($Platform->quoteIdentifier('throttleKey') . ' = :throttleKey')
            ->andWhere("($expiresAt <= :now OR $attempts < :limit)")
            ->setParameter('now', $now)
            ->setParameter('expiresAt', $now + $windowSeconds)
            ->setParameter('throttleKey', $throttleKey)
            ->setParameter('limit', $limit);

        if ((int)$Update->executeStatement() === 1) {
            return true;
        }

        try {
            // A savepoint keeps an enclosing PostgreSQL transaction usable on conflict.
            $Connection->transactional(static function () use (
                $Connection,
                $table,
                $throttleKey,
                $package,
                $subjectKey,
                $now,
                $windowSeconds,
                $Platform
            ): void {
                $Connection->insert($table, [
                    $Platform->quoteIdentifier('throttleKey') => $throttleKey,
                    $Platform->quoteIdentifier('package') => $package,
                    $Platform->quoteIdentifier('subjectKey') => $subjectKey,
                    $Platform->quoteIdentifier('reservationId') => '',
                    $Platform->quoteIdentifier('expiresAt') => $now + $windowSeconds,
                    $Platform->quoteIdentifier('attempts') => 1
                ]);
            });

            return true;
        } catch (UniqueConstraintViolationException) {
            // Another request may have inserted the first attempt: reserve remaining capacity.
            return (int)(clone $Update)->executeStatement() === 1;
        }
    }

    /**
     * Remove all throttle reservations belonging to a user.
     */
    public static function clearForUser(User $User): int
    {
        $userUuid = trim((string)$User->getUUID());

        if ($userUuid === '') {
            return 0;
        }

        return (int)QUI::getDataBaseConnection()->delete(
            QUI\Utils\Doctrine::quoteIdentifier(self::table()),
            ['subjectKey' => self::createHash("user\0" . $userUuid)]
        );
    }

    /**
     * Remove all throttle reservations belonging to a package.
     */
    public static function clearForPackage(string $package): int
    {
        $package = trim($package);

        if ($package === '') {
            return 0;
        }

        return (int)QUI::getDataBaseConnection()->delete(
            QUI\Utils\Doctrine::quoteIdentifier(self::table()),
            ['package' => $package]
        );
    }

    /**
     * Remove expired throttle reservations.
     */
    public static function cleanupExpired(): int
    {
        $Connection = QUI::getDataBaseConnection();
        $table = QUI\Utils\Doctrine::quoteIdentifier(self::table());

        return (int)$Connection->executeStatement(
            'DELETE FROM ' . $table . ' WHERE expiresAt <= ?',
            [time()]
        );
    }

    /**
     * Release one exact reservation after its protected operation failed.
     *
     * @internal Used by ThrottleDecision only.
     */
    public static function releaseReservation(string $throttleKey, string $reservationId): void
    {
        QUI::getDataBaseConnection()->delete(
            QUI\Utils\Doctrine::quoteIdentifier(self::table()),
            [
                'throttleKey' => $throttleKey,
                'reservationId' => $reservationId
            ]
        );
    }

    public static function table(): string
    {
        return QUI::getDBTableName('security_throttles');
    }

    private static function createHash(string $value): string
    {
        return hash('sha256', $value);
    }
}
