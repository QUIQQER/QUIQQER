<?php

declare(strict_types=1);

namespace QUI\System\Console\Tools;

use QUI;
use QUI\System\VhostManager;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

/**
 * Generate HTTP upgrades before the front controller, using the configured canonical hosts.
 */
class HttpsRedirects
{
    /**
     * @param array<array-key, mixed> $vhosts
     */
    public function __construct(
        private readonly array $vhosts,
        private readonly bool $forceHttps,
        private readonly string $wwwRedirect,
        private readonly string $globalHost,
        private readonly string $globalHttpsHost
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            QUI::vhosts(),
            (bool)QUI::conf('webserver', 'forceHttps'),
            (string)QUI::conf('webserver', 'wwwRedirect'),
            (string)QUI::conf('globals', 'host'),
            (string)QUI::conf('globals', 'httpshost')
        );
    }

    public function apache(): string
    {
        if (!$this->forceHttps) {
            return '';
        }

        // REQUEST_URI is decoded by mod_rewrite; retain the original escaped path in Location.
        $result = "    # Redirect HTTP directly to the configured HTTPS host.\n"
            . "    RewriteCond %{THE_REQUEST} \\s(?:https?://[^/\\s]+)?(/[^?\\s]*)\n"
            . "    RewriteRule ^ - [E=QUIQQER_HTTPS_PATH:%1]\n\n";

        foreach ($this->rules() as [$patterns, $target]) {
            $result .= "    RewriteCond %{HTTPS} !on\n";

            foreach ($patterns as $pattern) {
                $result .= "    RewriteCond %{HTTP_HOST} ^$pattern$ [NC]\n";
            }

            $result .= "    RewriteRule ^ https://$target%{ENV:QUIQQER_HTTPS_PATH} [R=301,END,NE]\n\n";
        }

        $fallback = $this->fallback() ?? '%{HTTP_HOST}';

        return $result . "    RewriteCond %{HTTPS} !on\n"
            . "    RewriteRule ^ https://$fallback%{ENV:QUIQQER_HTTPS_PATH} [R=301,END,NE]\n";
    }

    public function nginx(): string
    {
        if (!$this->forceHttps) {
            return '';
        }

        // A single condition works in a server include; nginx does not allow nested if directives.
        $result = "\n# Redirect HTTP directly to the configured HTTPS host.\n"
            . 'set $quiqqer_https_request "$scheme:$http_host";' . "\n";

        foreach ($this->rules() as [$patterns, $target]) {
            $pattern = array_pop($patterns);

            foreach (array_reverse($patterns) as $condition) {
                $pattern = '(?=' . $condition . '$)' . $pattern;
            }

            $pattern = str_replace('\\', '\\\\', $pattern);
            $target = str_replace('%1', '$1', $target);
            $result .= "if (\$quiqqer_https_request ~* \"^http:$pattern$\") {\n"
                . "    return 301 https://$target\$request_uri;\n}\n";
        }

        $fallback = $this->fallback() ?? '$host';

        return $result . "if (\$scheme != \"https\") {\n"
            . "    return 301 https://$fallback\$request_uri;\n}\n";
    }

    public function caddy(): string
    {
        if (!$this->forceHttps) {
            return '';
        }

        // Keep the redirect order explicit: concrete hosts must precede wildcard hosts and the fallback.
        $result = "\n# Redirect HTTP directly to the configured HTTPS host.\nroute {\n"
            . "    vars quiqqer_https_host {http.request.hostport}\n";

        foreach ($this->rules() as $index => [$patterns, $target]) {
            $name = 'quiqqer_https_' . $index;
            $conditions = [];

            foreach ($patterns as $pattern) {
                $pattern = str_replace('\\', '\\\\', $pattern);
                $conditions[] = "vars_regexp('$name', 'quiqqer_https_host', '(?i)^$pattern$')";
            }

            $expression = implode(' && ', $conditions);
            $target = str_replace('%1', '{re.' . $name . '.1}', $target);
            $result .= "    @$name {\n        protocol http\n        expression `$expression`\n    }\n"
                . "    redir @$name https://$target{uri} permanent\n";
        }

        $fallback = $this->fallback() ?? '{host}';

        return $result . "    @quiqqer_http protocol http\n"
            . "    redir @quiqqer_http https://$fallback{uri} permanent\n}\n";
    }

    /**
     * Exact hosts, HTTPS aliases and WWW counterparts precede wildcard routes, as in VhostManager.
     * %1 represents a host or port captured from a validated request authority.
     *
     * @return list<array{non-empty-list<string>, string}>
     */
    private function rules(): array
    {
        $hosts = [];

        foreach ($this->vhosts as $host => $data) {
            if (!is_string($host) || !is_array($data)) {
                continue;
            }

            $host = self::authority($host);
            $httpsHost = self::authority($data['httpshost'] ?? '');

            foreach ([$host, $httpsHost] as $candidate) {
                if ($candidate !== '' && !str_contains($candidate, '*')) {
                    $hostname = (string)parse_url('http://' . $candidate, PHP_URL_HOST);
                    $hosts[$hostname] = true;
                }
            }

            if ($host !== '' && !str_contains($host, '*')) {
                $hostname = (string)parse_url('http://' . $host, PHP_URL_HOST);
                $variant = VhostManager::getWwwRedirectHost(
                    $hostname,
                    str_starts_with($hostname, 'www.') ? 'nonwww' : 'www'
                );
                $hosts[$variant] = true;
            }
        }

        $rules = [];

        if ($this->vhosts === [] && $this->globalHost !== '') {
            $hostname = (string)parse_url('http://' . self::authority($this->globalHost), PHP_URL_HOST);
            $hosts[$hostname] = true;
        }

        foreach (array_keys($hosts) as $host) {
            $target = $this->target($host);

            if ($target === null) {
                continue;
            }

            // Only preserve an incoming port when PHP also preserves it for this route.
            $portTarget = $this->target($host . ':65534');
            $keepsPort = $portTarget === $target . ':65534';

            if ($keepsPort) {
                $rules[] = [[preg_quote($host, '/') . '(?::80)?'], $target];
            }

            $rules[] = [[preg_quote($host, '/') . '(:[0-9]+)?'], $target . ($keepsPort ? '%1' : '')];
        }

        foreach ($this->vhosts as $host => $data) {
            if (!is_string($host) || !is_array($data)) {
                continue;
            }

            $httpsHost = self::authority($data['httpshost'] ?? '');
            $mode = VhostManager::getWwwRedirect($data['wwwRedirect'] ?? '', $this->wwwRedirect);

            foreach ([self::authority($host), $httpsHost] as $pattern) {
                if (!str_contains($pattern, '*')) {
                    continue;
                }

                $pattern = (string)parse_url('http://' . $pattern, PHP_URL_HOST);
                $match = str_replace('\\*', '[a-z0-9._-]*', preg_quote($pattern, '/'));
                $port = '(?::[0-9]+)?';

                if ($httpsHost !== '' && !str_contains($httpsHost, '*')) {
                    $rules[] = [[$match . $port], $httpsHost];
                    continue;
                }

                if (str_contains($httpsHost, '*')) {
                    $httpsMatch = str_replace('\\*', '[a-z0-9._-]*', preg_quote($httpsHost, '/'));
                    $rules[] = [[$match . $port, '(' . $httpsMatch . ')' . $port], '%1'];
                }

                if ($mode === 'www' || $mode === 'nonwww') {
                    $rules[] = [
                        [$match . $port, '[a-z0-9_-]+\..*', '(?:www\.)?([a-z0-9._-]+)(?::80)?'],
                        ($mode === 'www' ? 'www.' : '') . '%1'
                    ];
                    $rules[] = [
                        [$match . $port, '[a-z0-9_-]+\..*', '(?:www\.)?([a-z0-9._-]+' . $port . ')'],
                        ($mode === 'www' ? 'www.' : '') . '%1'
                    ];
                }

                $rules[] = [['(' . $match . ')(?::80)?'], '%1'];
                $rules[] = [['(' . $match . $port . ')'], '%1'];
            }
        }

        return $rules;
    }

    private function target(string $host): ?string
    {
        $target = QUI\Rewrite::getCanonicalHostRedirectUrl(
            Request::create('http://' . $host . '/'),
            $this->vhosts,
            true,
            $this->wwwRedirect,
            $this->globalHost,
            $this->globalHttpsHost
        );

        return $target === null ? null : self::authority($target);
    }

    private function fallback(): ?string
    {
        // Match the PHP fallback: the first concrete VHost, otherwise the global host.
        foreach ($this->vhosts as $host => $data) {
            if (is_string($host) && is_array($data) && $host !== '' && !str_contains($host, '*')) {
                return $this->target(self::authority($host));
            }
        }

        $host = self::authority($this->globalHost);

        return $host === '' ? null : $this->target($host);
    }

    private static function authority(mixed $value): string
    {
        if (!is_string($value) || trim($value) === '') {
            return '';
        }

        if (preg_match('/[\x00-\x20\x7f]/', $value)) {
            throw new UnexpectedValueException('Invalid host in HTTPS redirect configuration.');
        }

        $parts = parse_url(str_contains($value, '://') ? $value : 'http://' . $value);
        $host = $parts['host'] ?? '';

        // Host settings must never introduce directives, variables or regex syntax into server configuration.
        if (!preg_match('/\A(?:[a-z0-9_.*-]+|\[[a-f0-9:]+\])\z/iD', $host)) {
            throw new UnexpectedValueException('Invalid host in HTTPS redirect configuration.');
        }

        return strtolower($host) . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }
}
