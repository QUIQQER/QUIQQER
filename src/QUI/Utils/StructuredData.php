<?php

namespace QUI\Utils;

/**
 * Date helpers shared by structured data producers.
 */
final class StructuredData
{
    /**
     * Return a date only when it can be parsed without warnings or errors.
     */
    public static function getValidDate(mixed $date): ?string
    {
        return self::parseDate($date) === null ? null : (string)$date;
    }

    /**
     * Return the edit date only when it represents a real modification.
     */
    public static function getModificationDate(mixed $creationDate, mixed $editDate): ?string
    {
        $CreationDate = self::parseDate($creationDate);
        $EditDate = self::parseDate($editDate);

        if ($CreationDate === null || $EditDate === null || $EditDate <= $CreationDate) {
            return null;
        }

        return $editDate;
    }

    private static function parseDate(mixed $date): ?\DateTimeImmutable
    {
        if (!is_string($date) || $date === '') {
            return null;
        }

        try {
            $Date = new \DateTimeImmutable($date);
        } catch (\Exception) {
            return null;
        }

        $errors = \DateTimeImmutable::getLastErrors();

        if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return null;
        }

        return $Date;
    }
}
