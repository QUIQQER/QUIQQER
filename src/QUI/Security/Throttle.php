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
 * Persistent, user-based throttling for security-sensitive actions.
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
