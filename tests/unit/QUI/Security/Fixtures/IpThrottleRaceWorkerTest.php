<?php

namespace QUI\Security\Fixtures;

use QUITests\Support\DatabaseEnvironment;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Security\Throttle;
use ReflectionProperty;

final class IpThrottleRaceWorkerTest extends TestCase
{
    public function testWorker(): void
    {
        $filename = getenv('QUIQQER_IP_THROTTLE_RACE_INPUT');

        if (!$filename) {
            self::markTestSkipped('Only executed by the concurrent IP throttle test.');
        }

        $input = json_decode(file_get_contents($filename), true, flags: JSON_THROW_ON_ERROR);
        $Connection = DatabaseEnvironment::createConnection($input['database']);

        if ($Connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SQLitePlatform) {
            $Connection->executeStatement('PRAGMA busy_timeout = 10000');
        }

        (new ReflectionProperty(QUI::class, 'QueryBuilder'))->setValue(null, $Connection);
        file_put_contents($input['ready'], 'ready');
        $deadline = microtime(true) + 15;

        while (!file_exists($input['go'])) {
            if (microtime(true) > $deadline) {
                self::fail('IP throttle race barrier timed out.');
            }

            usleep(10000);
        }

        $allowed = Throttle::acquireForIp($input['ip'], 'quiqqer/core', 'users.login', 2, 900);
        file_put_contents($input['result'], json_encode($allowed, JSON_THROW_ON_ERROR));
        self::assertIsBool($allowed);
        $Connection->close();
    }
}
