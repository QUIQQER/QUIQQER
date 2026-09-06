<?php

namespace QUI\Lock;

use QUI;
use Symfony\Component\Lock\LockFactory;

/** Configuration used by the administration control; secrets stay on the server. */
class Settings
{
    private QUI\Config $Config;

    public function __construct(?QUI\Config $Config = null)
    {
        $this->Config = $Config ?? QUI::getConfig('etc/conf.ini.php');
    }

    /** @return array<string, mixed> */
    public function get(): array
    {
        $dsn = $this->Config->get('locks', 'dsn') ?: 'flock';
        $result = [
            'backend' => 'custom',
            'namespace' => $this->Config->get('locks', 'namespace') ?: '',
            'path' => '', 'host' => '127.0.0.1', 'port' => 6379, 'database' => 2,
            'username' => '', 'tls' => false, 'password' => '', 'passwordConfigured' => false
        ];

        if ($dsn === 'flock' || str_starts_with($dsn, 'flock://')) {
            $result['backend'] = 'flock';
            $result['path'] = $dsn === 'flock' ? '' : substr($dsn, 8);
        } elseif ($dsn === 'dbal') {
            $result['backend'] = 'dbal';
        } elseif (preg_match('~^rediss?://~', $dsn)) {
            $url = parse_url($dsn);

            // Preserve advanced, manually configured DSNs rather than silently dropping their options.
            parse_str($url['query'] ?? '', $query);

            if ($url !== false && isset($url['host']) && !array_diff(array_keys($query), ['timeout', 'read_timeout'])) {
                $result['backend'] = 'redis';
                $result['host'] = trim($url['host'], '[]');
                $result['port'] = $url['port'] ?? 6379;
                $result['database'] = (int)ltrim($url['path'] ?? '/0', '/');
                $result['username'] = rawurldecode($url['user'] ?? '');
                $result['tls'] = ($url['scheme'] ?? '') === 'rediss';
                $result['passwordConfigured'] = isset($url['pass']) && $url['pass'] !== '';
            }
        }

        return $result;
    }

    /** @param array<string, mixed> $input
     * @return array{dsn: string, namespace: string}
     */
    public function normalize(array $input): array
    {
        $backend = $input['backend'] ?? '';
        $namespace = $this->text($input, 'namespace', 255);

        switch ($backend) {
            case 'flock':
                $path = $this->text($input, 'path', 4096);

                if ($path !== '' && !str_starts_with($path, '/')) {
                    $this->invalid();
                }

                $dsn = $path === '' ? 'flock' : 'flock://' . $path;
                break;

            case 'dbal':
                $dsn = 'dbal';
                break;

            case 'redis':
                $host = $this->text($input, 'host', 253);

                if (
                    $host === '' || (!filter_var($host, FILTER_VALIDATE_IP)
                    && !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME))
                ) {
                    $this->invalid();
                }

                $port = filter_var($input['port'] ?? null, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1, 'max_range' => 65535]
                ]);
                $database = filter_var($input['database'] ?? null, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 0, 'max_range' => 2147483647]
                ]);

                if ($port === false || $database === false) {
                    $this->invalid();
                }

                $username = $this->text($input, 'username', 255);
                $password = $input['password'] ?? '';

                if (!is_string($password) || strlen($password) > 4096 || preg_match('/[\x00-\x1f\x7f]/', $password)) {
                    $this->invalid();
                }

                if (!empty($input['clearPassword'])) {
                    $password = '';
                } elseif ($password === '' && $this->get()['backend'] === 'redis') {
                    $previous = parse_url($this->Config->get('locks', 'dsn'));
                    $password = rawurldecode($previous['pass'] ?? '');
                }

                $credentials = '';

                if ($username !== '' || $password !== '') {
                    $credentials = rawurlencode($username) . ':' . rawurlencode($password) . '@';
                }

                $host = str_contains($host, ':') ? '[' . $host . ']' : $host;
                $dsn = (!empty($input['tls']) ? 'rediss' : 'redis') . '://' . $credentials . $host
                    . ':' . $port . '/' . $database . '?timeout=3&read_timeout=3';
                break;

            case 'custom':
                if ($this->get()['backend'] !== 'custom') {
                    $this->invalid();
                }

                $dsn = $this->Config->get('locks', 'dsn');
                break;

            default:
                $this->invalid();
        }

        return ['dsn' => $dsn, 'namespace' => $namespace];
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function save(array $input): array
    {
        $values = $this->normalize($input);

        foreach ($values as $key => $value) {
            $this->Config->setValue('locks', $key, $value);
        }

        $this->Config->save();
        QUI::getConfig('etc/conf.ini.php')->reload();
        Locker::setProcessLockStore(null);

        return $this->get();
    }

    /** @param array<string, mixed> $input */
    public function test(array $input): bool
    {
        $values = $this->normalize($input);
        $First = $Second = null;
        $success = false;

        try {
            $Factory = new LockFactory(Locker::createProcessLockStore($values['dsn']));
            $key = 'quiqqer-process-test-' . bin2hex(random_bytes(16));
            $First = $Factory->createLock($key, 10);
            $Second = $Factory->createLock($key, 10);

            if ($First->acquire() && !$Second->acquire()) {
                $First->release();
                $success = $Second->acquire();
            }
        } catch (\Throwable) {
            // Connection exceptions may include a DSN or credentials. Never send them to the client.
            $success = false;
        } finally {
            foreach ([$First, $Second] as $Lock) {
                try {
                    $Lock?->release();
                } catch (\Throwable) {
                    // Expiring backends also release the test lease after ten seconds.
                    $success = false;
                }
            }
        }

        return $success;
    }

    /** @param array<string, mixed> $input */
    private function text(array $input, string $key, int $limit): string
    {
        $value = $input[$key] ?? '';

        if (!is_string($value) || strlen($value) > $limit || preg_match('/[\x00-\x1f\x7f]/', $value)) {
            $this->invalid();
        }

        return trim($value);
    }

    private function invalid(): never
    {
        throw new QUI\Exception(['quiqqer/core', 'processLocks.invalid'], 400);
    }
}
