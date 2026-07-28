<?php

/**
 * This file contains \QUI\Utils\Uuid
 */

namespace QUI\Utils;

use Ramsey\Uuid\FeatureSet;
use Ramsey\Uuid\Provider\Node\SystemNodeProvider;
use Ramsey\Uuid\UuidFactory;

use function microtime;
use function restore_error_handler;
use function set_error_handler;
use function str_starts_with;
use function uniqid;

use const E_WARNING;

/**
 * Class Uuid
 * - Helps to generate unique IDs
 */
class Uuid
{
    /**
     * Return a unique id
     */
    public static function get(): string
    {
        try {
            static $Factory = null;

            if ($Factory === null) {
                $Factory = self::createFactory();
            }

            $UUID = $Factory->uuid1();
        } catch (\Exception) {
            $UUID = \Ramsey\Uuid\Uuid::uuid3(
                \Ramsey\Uuid\Uuid::NAMESPACE_DNS,
                microtime(true) . uniqid()
            );
        }

        return $UUID->toString();
    }

    private static function createFactory(): UuidFactory
    {
        $FeatureSet = new FeatureSet(ignoreSystemNode: true);
        $SystemNodeProvider = new SystemNodeProvider();

        set_error_handler(
            static function (int $errorLevel, string $message): bool {
                return $errorLevel === E_WARNING
                    && str_starts_with($message, 'passthru(): Unable to fork');
            },
            E_WARNING
        );

        try {
            $SystemNodeProvider->getNode();
            $FeatureSet->setNodeProvider($SystemNodeProvider);
        } catch (\Ramsey\Uuid\Exception\NodeException) {
            // Keep the random node provider if the system node is unavailable.
        } finally {
            restore_error_handler();
        }

        return new UuidFactory($FeatureSet);
    }
}
