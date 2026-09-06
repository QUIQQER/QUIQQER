<?php

namespace QUI\Security;

use QUITests\Support\DatabaseEnvironment;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI;
use ReflectionProperty;

class IpThrottleConcurrencyTest extends TestCase
{
    public static function reservations(): array
    {
        return [['new'], ['expired']];
    }

    #[DataProvider('reservations')]
    public function testConcurrentRequestsRespectTheAttemptLimit(string $state): void
    {
        $dir = sys_get_temp_dir() . '/core-ip-throttle-race-' . bin2hex(random_bytes(8));
        mkdir($dir, 0700);
        $processes = [];
        $Original = QUI::getDataBaseConnection();
        $Property = new ReflectionProperty(QUI::class, 'QueryBuilder');
        $Shared = null;
        $ip = '2001:db8:' . implode(':', str_split(bin2hex(random_bytes(12)), 4));
        $otherIp = '2001:db8:' . implode(':', str_split(bin2hex(random_bytes(12)), 4));
        $subject = hash('sha256', "ip\0" . inet_pton($ip));
        $otherSubject = hash('sha256', "ip\0" . inet_pton($otherIp));
        $key = hash('sha256', $subject . "\0quiqqer/core\0users.login");
        $otherKey = hash('sha256', $otherSubject . "\0quiqqer/core\0users.login");

        try {
            $Shared = DatabaseEnvironment::createConnection($dir . '/database.sqlite');
            $Property->setValue(null, $Shared);
            if (!DatabaseEnvironment::usesCiDatabase()) {
                QUI\Update::importDatabase(dirname(__DIR__, 4) . '/database.xml');
            }
            if ($state === 'expired') {
                $Shared->insert(Throttle::table(), [
                    'throttleKey' => $key,
                    'package' => 'quiqqer/core', 'subjectKey' => $subject,
                    'reservationId' => '', 'attempts' => 2, 'expiresAt' => time() - 1
                ]);
            }
            $Property->setValue(null, $Original);

            foreach ([0, 1, 2, 3] as $i) {
                $input = $dir . '/input-' . $i;
                file_put_contents($input, json_encode([
                    'database' => $dir . '/database.sqlite', 'ip' => $ip, 'ready' => $dir . '/ready-' . $i,
                    'go' => $dir . '/go', 'result' => $dir . '/result-' . $i
                ], JSON_THROW_ON_ERROR));
                $process = proc_open(
                    [
                    PHP_BINARY, dirname(__DIR__, 4) . '/tools/phpunit', '--no-configuration',
                    '--bootstrap', dirname(__DIR__, 3) . '/phpunit-bootstrap.php',
                    __DIR__ . '/Fixtures/IpThrottleRaceWorkerTest.php'
                    ],
                    [1 => ['file', $dir . '/output-' . $i, 'w'], 2 => ['file', $dir . '/error-' . $i, 'w']],
                    $pipes,
                    null,
                    array_replace(getenv(), ['QUIQQER_IP_THROTTLE_RACE_INPUT' => $input])
                );
                self::assertIsResource($process);
                $processes[] = $process;
            }
            $deadline = microtime(true) + 20;
            while (count(glob($dir . '/ready-*')) < 4) {
                if (microtime(true) > $deadline) {
                    self::fail('IP throttle workers did not reach the barrier: ' . $this->workerOutput($dir));
                }
                usleep(10000);
            }
            file_put_contents($dir . '/go', 'go');
            foreach ($processes as $process) {
                self::assertSame(0, proc_close($process), $this->workerOutput($dir));
            }
            $processes = [];
            $results = [];
            foreach ([0, 1, 2, 3] as $i) {
                $results[] = json_decode(file_get_contents($dir . '/result-' . $i), true, flags: JSON_THROW_ON_ERROR);
            }
            self::assertCount(2, array_filter($results));
            self::assertCount(1, $Shared->createQueryBuilder()->select('*')->from(Throttle::table())
                ->where('throttleKey = :key')->setParameter('key', $key)->executeQuery()->fetchAllAssociative());
            self::assertSame(2, (int)$Shared->fetchOne('SELECT attempts FROM ' . Throttle::table() . ' WHERE throttleKey = ?', [$key]));
            $Property->setValue(null, $Shared);
            $Shared->transactional(static function () use ($Shared, $ip, $otherIp, $key, $otherKey): void {
                self::assertFalse(Throttle::acquireForIp($ip, 'quiqqer/core', 'users.login', 2, 900));
                // A denied INSERT must not poison PostgreSQL's surrounding transaction.
                self::assertTrue(Throttle::acquireForIp($otherIp, 'quiqqer/core', 'users.login', 2, 900));
                self::assertCount(2, $Shared->createQueryBuilder()->select('*')->from(Throttle::table())
                    ->where('throttleKey IN (:key, :otherKey)')
                    ->setParameter('key', $key)->setParameter('otherKey', $otherKey)
                    ->executeQuery()->fetchAllAssociative());
            });
        } finally {
            $Property->setValue(null, $Original);
            foreach ($processes as $process) {
                if (is_resource($process)) {
                    proc_terminate($process);
                    proc_close($process);
                }
            }
            if ($Shared !== null) {
                foreach ([$key, $otherKey] as $ownedKey) {
                    $Shared->delete(Throttle::table(), ['throttleKey' => $ownedKey]);
                }
                $Shared->close();
            }
            foreach (glob($dir . '/*') as $file) {
                unlink($file);
            }
            rmdir($dir);
        }
    }

    private function workerOutput(string $dir): string
    {
        $output = '';
        foreach (array_merge(glob($dir . '/output-*'), glob($dir . '/error-*')) as $file) {
            $output .= file_get_contents($file);
        }
        return $output;
    }
}
