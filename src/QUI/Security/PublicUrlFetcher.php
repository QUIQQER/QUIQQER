<?php

/**
 * This file contains the \QUI\Security\PublicUrlFetcher
 */

namespace QUI\Security;

use CurlHandle;
use QUI\Exception;

use function array_key_exists;
use function array_pop;
use function curl_close;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt_array;
use function dns_get_record;
use function explode;
use function filter_var;
use function gethostbynamel;
use function idn_to_ascii;
use function in_array;
use function inet_pton;
use function implode;
use function is_array;
use function is_string;
use function ord;
use function parse_url;
use function preg_match;
use function preg_replace;
use function rtrim;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strtolower;
use function strlen;
use function strpos;
use function strrpos;
use function substr;
use function trim;

use const CURLINFO_PRIMARY_IP;
use const CURLINFO_RESPONSE_CODE;
use const CURLPROTO_HTTP;
use const CURLPROTO_HTTPS;
use const CURLOPT_CONNECTTIMEOUT;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HEADERFUNCTION;
use const CURLOPT_PROTOCOLS;
use const CURLOPT_PROXY;
use const CURLOPT_REDIR_PROTOCOLS;
use const CURLOPT_RESOLVE;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_URL;
use const DNS_A;
use const DNS_AAAA;
use const FILTER_FLAG_GLOBAL_RANGE;
use const FILTER_VALIDATE_IP;
use const IDNA_DEFAULT;
use const INTL_IDNA_VARIANT_UTS46;
use const PHP_URL_SCHEME;

/**
 * Retrieves HTTP(S) resources without permitting access to local or private networks.
 */
class PublicUrlFetcher
{
    private const MAX_REDIRECTS = 5;

    /**
     * @throws Exception
     */
    public function get(string $url): string
    {
        for ($redirects = 0; $redirects <= self::MAX_REDIRECTS; $redirects++) {
            $Destination = $this->resolveDestination($url);
            $Response = $this->request(
                $Destination['url'],
                $Destination['host'],
                $Destination['port'],
                $Destination['addresses']
            );

            if (!in_array($Response['status'], [301, 302, 303, 307, 308], true)) {
                return $Response['body'];
            }

            if ($Response['location'] === null || $Response['location'] === '') {
                return $Response['body'];
            }

            if ($redirects === self::MAX_REDIRECTS) {
                throw new Exception('The external URL redirects too often.');
            }

            $url = $this->resolveRedirect($Destination['url'], $Response['location']);
        }

        throw new Exception('The external URL could not be retrieved.');
    }

    /**
     * @return array{url: non-empty-string, host: string, port: int, addresses: non-empty-list<string>}
     * @throws Exception
     */
    private function resolveDestination(string $url): array
    {
        if (
            $url === ''
            || preg_match('/[\x00-\x20\x7f]/', $url) === 1
            || str_contains($url, '\\')
        ) {
            throw new Exception('The external URL is invalid.');
        }

        $Parts = parse_url($url);

        if (!is_array($Parts)) {
            throw new Exception('The external URL is invalid.');
        }

        $scheme = strtolower((string)($Parts['scheme'] ?? ''));
        $host = (string)($Parts['host'] ?? '');

        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new Exception('Only HTTP(S) external URLs are permitted.');
        }

        if (isset($Parts['user']) || isset($Parts['pass'])) {
            throw new Exception('Credentials are not permitted in external URLs.');
        }

        $host = trim($host, '[]');

        if ($host === '' || str_contains($host, '%')) {
            throw new Exception('The external URL host is invalid.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP) === false) {
            $asciiHost = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

            if (!is_string($asciiHost) || $asciiHost === '') {
                throw new Exception('The external URL host is invalid.');
            }

            $host = strtolower(rtrim($asciiHost, '.'));
        }

        $port = (int)($Parts['port'] ?? ($scheme === 'https' ? 443 : 80));

        if ($port < 1) {
            throw new Exception('The external URL port is invalid.');
        }

        $addresses = $this->resolveHost($host);
        $publicAddresses = [];

        foreach ($addresses as $address) {
            if ($this->isPublicAddress($address) && !in_array($address, $publicAddresses, true)) {
                $publicAddresses[] = $address;
            }
        }

        if ($publicAddresses === []) {
            throw new Exception('The external URL does not resolve to a public address.');
        }

        $urlHost = str_contains($host, ':') ? '[' . $host . ']' : $host;
        $authority = $scheme . '://' . $urlHost;

        if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
            $authority .= ':' . $port;
        }

        $path = (string)($Parts['path'] ?? '/');

        if ($path === '') {
            $path = '/';
        }

        $normalizedUrl = $authority . $path;

        if (array_key_exists('query', $Parts)) {
            $normalizedUrl .= '?' . (string)$Parts['query'];
        }

        return [
            'url' => $normalizedUrl,
            'host' => $host,
            'port' => $port,
            'addresses' => $publicAddresses
        ];
    }

    /**
     * @return list<string>
     */
    protected function resolveHost(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $addresses = [];
        $Records = dns_get_record($host, DNS_A | DNS_AAAA);

        if (is_array($Records)) {
            foreach ($Records as $Record) {
                $address = $Record['ip'] ?? $Record['ipv6'] ?? null;

                if (is_string($address)) {
                    $addresses[] = $address;
                }
            }
        }

        if ($addresses === []) {
            $ipv4Addresses = gethostbynamel($host);

            if (is_array($ipv4Addresses)) {
                $addresses = $ipv4Addresses;
            }
        }

        return $addresses;
    }

    protected function isPublicAddress(string $address): bool
    {
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE) === false) {
            return false;
        }

        $binaryAddress = inet_pton($address);

        if ($binaryAddress === false) {
            return false;
        }

        if (strlen($binaryAddress) === 4) {
            return ord($binaryAddress[0]) < 224;
        }

        if (ord($binaryAddress[0]) === 255) {
            return false;
        }

        $nat64Prefix = inet_pton('64:ff9b::');
        $localNat64Prefix = inet_pton('64:ff9b:1::');

        return is_string($nat64Prefix)
            && is_string($localNat64Prefix)
            && !str_starts_with($binaryAddress, substr($nat64Prefix, 0, 12))
            && !str_starts_with($binaryAddress, substr($localNat64Prefix, 0, 6));
    }

    /**
     * @param non-empty-string $url
     * @param non-empty-list<string> $addresses
     * @return array{status: int, location: string|null, body: string}
     * @throws Exception
     */
    protected function request(string $url, string $host, int $port, array $addresses): array
    {
        $lastError = '';

        foreach ($addresses as $address) {
            $Curl = curl_init();

            if (!$Curl instanceof CurlHandle) {
                throw new Exception('Could not initialize cURL.');
            }

            $headers = [];
            $resolveAddress = str_contains($address, ':') ? '[' . $address . ']' : $address;
            $resolveHost = str_contains($host, ':') ? '[' . $host . ']' : $host;

            curl_setopt_array($Curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
                CURLOPT_PROXY => '',
                CURLOPT_RESOLVE => [$resolveHost . ':' . $port . ':' . $resolveAddress],
                CURLOPT_HEADERFUNCTION => static function (CurlHandle $Curl, string $line) use (&$headers): int {
                    if (str_starts_with(strtolower($line), 'http/')) {
                        $headers = [];
                        return strlen($line);
                    }

                    $separator = strpos($line, ':');

                    if ($separator !== false) {
                        $name = strtolower(trim(substr($line, 0, $separator)));
                        $headers[$name] = trim(substr($line, $separator + 1));
                    }

                    return strlen($line);
                }
            ]);

            $body = curl_exec($Curl);
            $primaryAddress = (string)curl_getinfo($Curl, CURLINFO_PRIMARY_IP);
            $status = (int)curl_getinfo($Curl, CURLINFO_RESPONSE_CODE);
            $lastError = curl_error($Curl);
            curl_close($Curl);

            if (!is_string($body)) {
                continue;
            }

            if (!$this->addressesMatch($address, $primaryAddress)) {
                throw new Exception('The external URL connected to an unexpected address.');
            }

            return [
                'status' => $status,
                'location' => isset($headers['location']) ? (string)$headers['location'] : null,
                'body' => $body
            ];
        }

        throw new Exception('Error at external URL request: ' . $lastError);
    }

    private function addressesMatch(string $expected, string $actual): bool
    {
        $expectedBinary = inet_pton($expected);
        $actualBinary = inet_pton($actual);

        return $expectedBinary !== false && $expectedBinary === $actualBinary;
    }

    /**
     * @throws Exception
     */
    private function resolveRedirect(string $baseUrl, string $location): string
    {
        if (
            $location === ''
            || preg_match('/[\x00-\x20\x7f]/', $location) === 1
            || str_contains($location, '\\')
        ) {
            throw new Exception('The external URL redirect is invalid.');
        }

        if (parse_url($location, PHP_URL_SCHEME) !== null) {
            return $location;
        }

        $Base = parse_url($baseUrl);

        if (!is_array($Base) || !isset($Base['scheme'], $Base['host'])) {
            throw new Exception('The external URL redirect is invalid.');
        }

        if (str_starts_with($location, '//')) {
            return (string)$Base['scheme'] . ':' . $location;
        }

        $baseHost = (string)$Base['host'];
        $urlHost = str_contains($baseHost, ':') ? '[' . trim($baseHost, '[]') . ']' : $baseHost;
        $origin = (string)$Base['scheme'] . '://' . $urlHost;

        if (isset($Base['port'])) {
            $origin .= ':' . (int)$Base['port'];
        }

        $location = preg_replace('/#.*$/', '', $location) ?? '';

        if (str_starts_with($location, '?')) {
            return $origin . (string)($Base['path'] ?? '/') . $location;
        }

        $Location = parse_url($location);

        if (!is_array($Location)) {
            throw new Exception('The external URL redirect is invalid.');
        }

        $path = (string)($Location['path'] ?? '');

        if (!str_starts_with($path, '/')) {
            $basePath = (string)($Base['path'] ?? '/');
            $path = substr($basePath, 0, (int)strrpos($basePath, '/') + 1) . $path;
        }

        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);
                continue;
            }

            $segments[] = $segment;
        }

        $path = '/' . implode('/', $segments);

        if (str_ends_with((string)($Location['path'] ?? ''), '/') && !str_ends_with($path, '/')) {
            $path .= '/';
        }

        if (array_key_exists('query', $Location)) {
            $path .= '?' . (string)$Location['query'];
        }

        return $origin . $path;
    }
}
