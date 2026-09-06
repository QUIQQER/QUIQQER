<?php

namespace QUI\Lock;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI;

class SettingsTest extends TestCase
{
    private function settings(string $dsn = 'flock'): Settings
    {
        $Config = $this->createMock(QUI\Config::class);
        $Config->method('get')->willReturnCallback(static fn(string $section, ?string $key = null): mixed =>
            $section === 'locks' ? ['dsn' => $dsn, 'namespace' => 'test-installation'][$key] ?? false : false);

        return new Settings($Config);
    }

    public function testRedisCredentialsAreNeverReturned(): void
    {
        $values = $this->settings('rediss://alice:secret%40pass@redis.example.org:6380/2')->get();
        self::assertSame('redis', $values['backend']);
        self::assertSame('alice', $values['username']);
        self::assertSame('', $values['password']);
        self::assertTrue($values['passwordConfigured']);
        self::assertTrue($values['tls']);
        self::assertStringNotContainsString('secret', json_encode($values));
        self::assertArrayNotHasKey('dsn', $values);
    }

    public function testEmptyPasswordPreservesTheStoredSecretAndExplicitRemovalClearsIt(): void
    {
        $Settings = $this->settings('redis://alice:secret%40pass@127.0.0.1:6379/2');
        $input = $Settings->get();
        $values = $Settings->normalize($input);
        self::assertStringContainsString('alice:secret%40pass@', $values['dsn']);
        $input['clearPassword'] = true;
        self::assertStringNotContainsString('secret', $Settings->normalize($input)['dsn']);
        $input['clearPassword'] = false;
        $input['password'] = 'new:@/# password';
        self::assertStringContainsString(rawurlencode($input['password']), $Settings->normalize($input)['dsn']);
    }

    public function testIpv6AndTlsProduceAUsableDsn(): void
    {
        $input = $this->settings()->get();
        $input['backend'] = 'redis';
        $input['host'] = '::1';
        $input['tls'] = true;
        $dsn = $this->settings()->normalize($input)['dsn'];
        self::assertSame('rediss://[::1]:6379/2?timeout=3&read_timeout=3', $dsn);
        self::assertSame('::1', $this->settings($dsn)->get()['host']);
    }

    public function testCustomConnectionIsPreservedWithoutExposingItsCredentials(): void
    {
        $dsn = 'mysql://alice:secret@db.example.org/locks';
        $Settings = $this->settings($dsn);
        $input = $Settings->get();
        self::assertSame('custom', $input['backend']);
        self::assertStringNotContainsString('secret', json_encode($input));
        self::assertSame($dsn, $Settings->normalize($input)['dsn']);
    }

    public function testAdvancedRedisConfigurationIsNotSilentlyRewritten(): void
    {
        $dsn = 'redis://alice:secret@redis.example.org/2?redis_cluster=1';
        $Settings = $this->settings($dsn);
        self::assertSame('custom', $Settings->get()['backend']);
        self::assertSame($dsn, $Settings->normalize($Settings->get())['dsn']);
    }

    public function testConnectionCheckUsesACandidateWithoutChangingConfigurationOrActiveFactory(): void
    {
        $directory = sys_get_temp_dir() . '/quiqqer-lock-settings-' . bin2hex(random_bytes(8));
        $Store = new \Symfony\Component\Lock\Store\FlockStore($directory);
        Locker::setProcessLockStore($Store);
        $Property = new \ReflectionProperty(Locker::class, 'ProcessLockFactory');
        $Factory = $Property->getValue();
        $Settings = $this->settings();

        try {
            self::assertTrue($Settings->test(['backend' => 'flock', 'path' => $directory]));
            self::assertSame($Factory, $Property->getValue());
            self::assertSame('flock', $Settings->get()['backend']);
            self::assertSame('', $Settings->get()['path']);
        } finally {
            Locker::setProcessLockStore(null);

            foreach (glob($directory . '/*') ?: [] as $file) {
                unlink($file);
            }

            rmdir($directory);
        }
    }

    public function testFailedConnectionDoesNotExposeBackendException(): void
    {
        $directory = sys_get_temp_dir() . '/quiqqer-lock-settings-' . bin2hex(random_bytes(8));
        file_put_contents($directory, 'not a directory');

        try {
            self::assertFalse($this->settings()->test(['backend' => 'flock', 'path' => $directory]));
        } finally {
            unlink($directory);
        }
    }

    public function testSavePreservesUnrelatedSettingsAndReturnsOnlyPublicValues(): void
    {
        $directory = sys_get_temp_dir() . '/quiqqer-lock-save-' . bin2hex(random_bytes(8));
        mkdir($directory);
        $file = $directory . '/config.ini.php';
        file_put_contents($file, ";<?php exit; ?>\n[unrelated]\nvalue = keep\n");
        $Config = new QUI\Config($file);
        $Settings = new Settings($Config);
        $input = $Settings->get();
        $input['backend'] = 'redis';
        $input['password'] = 'private-secret';

        try {
            $result = $Settings->save($input);
            $Config->reload();
            self::assertSame('keep', $Config->get('unrelated', 'value'));
            self::assertStringContainsString('private-secret', $Config->get('locks', 'dsn'));
            self::assertSame('', $result['password']);
            self::assertTrue($result['passwordConfigured']);
        } finally {
            foreach (glob($directory . '/*') ?: [] as $entry) {
                unlink($entry);
            }

            rmdir($directory);
        }
    }

    #[DataProvider('invalidInputs')]
    public function testInvalidConfigurationIsRejected(array $input): void
    {
        $this->expectException(QUI\Exception::class);
        $this->expectExceptionCode(400);
        $this->settings()->normalize($input);
    }

    public static function invalidInputs(): array
    {
        $redis = ['backend' => 'redis', 'host' => 'localhost', 'port' => 6379, 'database' => 2];
        return [
            [[]], [['backend' => 'null']], [['backend' => 'custom']],
            [['backend' => 'flock', 'path' => '../relative']],
            [['backend' => 'dbal', 'namespace' => "bad\nnamespace"]],
            [array_replace($redis, ['host' => 'host/path'])],
            [array_replace($redis, ['port' => 0])],
            [array_replace($redis, ['port' => 65536])],
            [array_replace($redis, ['database' => -1])],
            [array_replace($redis, ['password' => ['invalid']])]
        ];
    }
}
