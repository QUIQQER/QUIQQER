<?php

/**
 * This file contains \QUI\System\VhostManager
 */

namespace QUI\System;

use QUI;
use QUI\Config;
use QUI\Exception;
use QUI\Projects\Manager as ProjectManager;
use QUI\Utils\Security\Orthos;

use function array_unique;
use function explode;
use function file_exists;
use function file_put_contents;
use function in_array;
use function is_array;
use function is_string;
use function parse_url;
use function rtrim;
use function strtolower;
use function str_replace;
use function trim;

/**
 * Virtual Host Manager
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 *
 * @todo    vhosts permissions
 */
class VhostManager
{
    public const PATH_LANGUAGES_CONFIG_KEY = 'path_langs';
    public const WWW_REDIRECT_CONFIG_KEY = 'wwwRedirect';

    protected ?Config $Config = null;

    /**
     * Resolve an optional VHost override against the global WWW preference.
     */
    public static function getWwwRedirect(mixed $value, string $global): string
    {
        if (in_array($value, ['www', 'nonwww', 'none'], true)) {
            return $value;
        }

        return in_array($global, ['www', 'nonwww'], true) ? $global : 'none';
    }

    public static function getWwwRedirectHost(string $host, string $mode): string
    {
        $hostname = self::getHostname($host);

        if (
            !str_contains($hostname, '.')
            || str_contains($hostname, '*')
            || filter_var(trim($hostname, '[]'), FILTER_VALIDATE_IP)
        ) {
            return $host;
        }

        if ($mode === 'www' && !str_starts_with(strtolower($host), 'www.')) {
            return 'www.' . $host;
        }

        if ($mode === 'nonwww' && str_starts_with(strtolower($host), 'www.')) {
            return substr($host, 4);
        }

        return $host;
    }

    /**
     * Explicit hosts take precedence over HTTPS aliases, WWW variants and wildcards.
     *
     * @param array<array-key, mixed> $vhosts
     */
    public static function findVhost(string $requestHost, array $vhosts): ?string
    {
        foreach ($vhosts as $host => $data) {
            if (is_string($host) && is_array($data) && strtolower($host) === strtolower($requestHost)) {
                return $host;
            }
        }

        $requestHost = self::getHostname($requestHost);

        if ($requestHost === '') {
            return null;
        }

        foreach ($vhosts as $host => $data) {
            if (is_string($host) && is_array($data) && self::getHostname($host) === $requestHost) {
                return $host;
            }
        }

        foreach ($vhosts as $host => $data) {
            if (
                is_string($host)
                && is_array($data)
                && is_string($data['httpshost'] ?? null)
                && self::getHostname($data['httpshost']) === $requestHost
            ) {
                return $host;
            }
        }

        foreach ($vhosts as $host => $data) {
            if (!is_string($host) || !is_array($data)) {
                continue;
            }

            $hostname = self::getHostname($host);
            $variant = self::getWwwRedirectHost(
                $hostname,
                str_starts_with($hostname, 'www.') ? 'nonwww' : 'www'
            );

            if ($variant !== $hostname && $variant === $requestHost) {
                return $host;
            }
        }

        foreach ($vhosts as $host => $data) {
            if (!is_string($host) || !is_array($data)) {
                continue;
            }

            foreach ([$host, $data['httpshost'] ?? ''] as $pattern) {
                if (is_string($pattern) && str_contains($pattern, '*')) {
                    if (fnmatch(self::getHostname($pattern), $requestHost, FNM_CASEFOLD)) {
                        return $host;
                    }
                }
            }
        }

        return null;
    }

    private static function getHostname(string $host): string
    {
        $hostname = parse_url(str_contains($host, '://') ? $host : '//' . $host, PHP_URL_HOST);

        return is_string($hostname) ? strtolower($hostname) : '';
    }

    /**
     * Add a vhost
     *
     * @param string $vhost - host name (eq: www.something.com)
     *
     * @return string - clean vhost
     * @throws Exception
     */
    public function addVhost(string $vhost): string
    {
        if (str_contains($vhost, '://')) {
            $parts = explode('://', $vhost);
            $vhost = $parts[1];
        }

        $vhost = trim($vhost, '/');
        $Config = $this->getConfig();

        if ($Config->existValue($vhost)) {
            throw new Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.vhost.exist'
                )
            );
        }

        $Config->setSection($vhost);
        $Config->save();

        $this->repair();

        return $vhost;
    }

    /**
     * @throws Exception
     */
    protected function getConfig(): Config
    {
        if (!file_exists(ETC_DIR . 'vhosts.ini.php')) {
            file_put_contents(ETC_DIR . 'vhosts.ini.php', '');
        }

        $this->Config = new Config(ETC_DIR . 'vhosts.ini.php');

        return $this->Config;
    }

    /**
     * Check the vhosts entry and tries to repair it
     * eq. search empty language entries
     * @throws Exception
     */
    public function repair(): void
    {
        $Config = $this->getConfig();
        $list = $this->getList();

        // Normalize path language entries without assigning newly added
        // project languages automatically.
        foreach ($list as $host => $data) {
            if ((int)$host) {
                continue;
            }

            if (!isset($data['project'])) {
                continue;
            }

            if (!isset($data['lang'])) {
                continue;
            }

            $Config->setValue(
                $host,
                self::PATH_LANGUAGES_CONFIG_KEY,
                implode(',', self::parsePathLanguages($data[self::PATH_LANGUAGES_CONFIG_KEY] ?? ''))
            );
        }

        $Config->save();

        // clearing cache
        QUI\Cache\Manager::clear();
    }

    /**
     * Return the vhost list
     *
     * @return array<string, array<string, mixed>>
     *
     * @throws Exception
     */
    public function getList(): array
    {
        return $this->getConfig()->toArray();
    }

    /**
     * Return the host, is a host is set for a project
     *
     * @param string $projectName - Name of the project
     * @param string $projectLang - Language of the project (de, en, etc...)
     *
     * @return string
     * @throws Exception
     */
    public function getHostByProject(string $projectName, string $projectLang): string
    {
        $config = $this->getList();

        foreach ($config as $host => $data) {
            if (!isset($data['project'])) {
                continue;
            }

            if (!isset($data['lang'])) {
                continue;
            }

            if ($data['project'] != $projectName) {
                continue;
            }

            if ($data['lang'] != $projectLang) {
                continue;
            }

            return $host;
        }

        return '';
    }

    /**
     * Return the canonical VHost route for a project language.
     *
     * Root languages are hosted at the VHost root. Path languages are hosted
     * below /<language>/. Legacy language-to-host assignments remain readable
     * as a fallback for existing installations.
     *
     * @return array{
     *     host: string,
     *     httpshost: string,
     *     path: string,
     *     project: string,
     *     lang: string
     * }|null
     *
     * @throws Exception
     */
    public function getProjectLanguageRoute(string $projectName, string $projectLang): ?array
    {
        $vhosts = $this->getList();
        $route = self::resolveProjectLanguageRoute(
            $vhosts,
            $projectName,
            $projectLang
        );

        if ($route !== null) {
            $host = self::findVhost($route['host'], $vhosts);
            $mode = self::getWwwRedirect(
                $host === null ? '' : ($vhosts[$host][self::WWW_REDIRECT_CONFIG_KEY] ?? ''),
                (string)QUI::conf('webserver', 'wwwRedirect')
            );
            $route['host'] = self::getWwwRedirectHost($route['host'], $mode);
        }

        return $route;
    }

    /**
     * Resolve a canonical project language route from VHost configuration.
     *
     * @param array<string|int, array<string, mixed>> $config
     *
     * @return array{
     *     host: string,
     *     httpshost: string,
     *     path: string,
     *     project: string,
     *     lang: string
     * }|null
     */
    public static function resolveProjectLanguageRoute(
        array $config,
        string $projectName,
        string $projectLang
    ): ?array {
        $projectLang = strtolower(trim($projectLang));

        if ($projectName === '' || $projectLang === '') {
            return null;
        }

        // A root language route always takes precedence.
        foreach ($config as $host => $data) {
            if (
                !is_string($host)
                || ($data['project'] ?? null) !== $projectName
                || ($data['lang'] ?? null) !== $projectLang
            ) {
                continue;
            }

            return self::createLanguageRoute($host, $data, $projectName, $projectLang);
        }

        // A path language is owned by exactly one VHost.
        foreach ($config as $host => $data) {
            if (
                !is_string($host)
                || ($data['project'] ?? null) !== $projectName
                || !in_array(
                    $projectLang,
                    self::parsePathLanguages($data[self::PATH_LANGUAGES_CONFIG_KEY] ?? ''),
                    true
                )
            ) {
                continue;
            }

            return self::createLanguageRoute(
                $host,
                $data,
                $projectName,
                $projectLang,
                $projectLang
            );
        }

        // Backward compatibility for the former per-language host fields.
        foreach ($config as $data) {
            if (
                ($data['project'] ?? null) !== $projectName
                || empty($data[$projectLang])
                || !is_string($data[$projectLang])
            ) {
                continue;
            }

            $legacyRoute = self::parseLegacyLanguageRoute($data[$projectLang]);

            if ($legacyRoute === null) {
                continue;
            }

            $targetData = [];

            if (isset($config[$legacyRoute['host']])) {
                $targetData = $config[$legacyRoute['host']];
            }

            return self::createLanguageRoute(
                $legacyRoute['host'],
                $targetData,
                $projectName,
                $projectLang,
                $legacyRoute['path']
            );
        }

        return null;
    }

    /**
     * Parse the comma-separated path language configuration.
     *
     * @return array<int, string>
     */
    public static function parsePathLanguages(mixed $languages): array
    {
        if (is_string($languages)) {
            $languages = explode(',', $languages);
        }

        if (!is_array($languages)) {
            return [];
        }

        $result = [];

        foreach ($languages as $language) {
            if (!is_string($language)) {
                continue;
            }

            $language = strtolower(trim($language));

            if (strlen($language) !== 2 || in_array($language, $result, true)) {
                continue;
            }

            $result[] = $language;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array{
     *     host: string,
     *     httpshost: string,
     *     path: string,
     *     project: string,
     *     lang: string
     * }
     */
    private static function createLanguageRoute(
        string $host,
        array $data,
        string $projectName,
        string $projectLang,
        string $path = ''
    ): array {
        $httpsHost = '';

        if (!empty($data['httpshost']) && is_string($data['httpshost'])) {
            $httpsHost = trim($data['httpshost'], '/');
        }

        return [
            'host' => trim($host, '/'),
            'httpshost' => $httpsHost,
            'path' => trim($path, '/'),
            'project' => $projectName,
            'lang' => $projectLang
        ];
    }

    /**
     * @return array{host: string, path: string}|null
     */
    private static function parseLegacyLanguageRoute(string $route): ?array
    {
        $route = trim($route);

        if ($route === '') {
            return null;
        }

        if (!str_contains($route, '://')) {
            $route = '//' . $route;
        }

        $parts = parse_url($route);

        if ($parts === false || empty($parts['host'])) {
            return null;
        }

        return [
            'host' => $parts['host'],
            'path' => isset($parts['path'])
                ? trim($parts['path'], '/')
                : ''
        ];
    }

    /**
     * Add or edit a vhost entry
     *
     * @param string $vhost - host name (eq: www.something.com)
     * @param array<string, mixed> $data - data of the host
     *
     * @throws Exception
     */
    public function editVhost(string $vhost, array $data): void
    {
        $Config = $this->getConfig();

        if (!$Config->existValue($vhost)) {
            throw new Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.vhost.not.found'
                )
            );
        }

        if (
            array_key_exists(self::WWW_REDIRECT_CONFIG_KEY, $data)
            && !in_array($data[self::WWW_REDIRECT_CONFIG_KEY], ['', 'www', 'nonwww', 'none'], true)
        ) {
            throw new Exception(['quiqqer/core', 'exception.vhost.invalid.wwwRedirect']);
        }

        $existing = $Config->getSection($vhost);
        $data[self::WWW_REDIRECT_CONFIG_KEY] ??= is_array($existing)
            ? ($existing[self::WWW_REDIRECT_CONFIG_KEY] ?? '')
            : '';

        $globalWwwRedirect = (string)QUI::conf('webserver', 'wwwRedirect');
        $targetHost = self::getWwwRedirectHost(
            $vhost,
            self::getWwwRedirect($data[self::WWW_REDIRECT_CONFIG_KEY], $globalWwwRedirect)
        );

        if ($targetHost !== $vhost) {
            foreach ($Config->toArray() as $otherHost => $otherData) {
                if (!is_string($otherHost) || self::getHostname($otherHost) !== self::getHostname($targetHost)) {
                    continue;
                }

                $otherMode = self::getWwwRedirect(
                    $otherData[self::WWW_REDIRECT_CONFIG_KEY] ?? '',
                    $globalWwwRedirect
                );

                if (self::getWwwRedirectHost($targetHost, $otherMode) !== $targetHost) {
                    throw new Exception([
                        'quiqqer/core',
                        'exception.vhost.conflicting.wwwRedirect',
                        ['host' => $otherHost]
                    ]);
                }
            }
        }

        $result = [];

        foreach ($data as $key => $value) {
            $key = Orthos::clear($key);

            $result[$key] = $value;
        }

        if (!isset($result["project"])) {
            throw new Exception([
                'quiqqer/core',
                'exception.vhost.missing.data.project'
            ]);
        }

        if (empty($result['lang']) || !is_string($result['lang'])) {
            throw new Exception([
                'quiqqer/core',
                'exception.vhost.missing.data.lang'
            ]);
        }

        $Project = QUI::getProject($result['project']);
        $projectLanguages = $Project->getLanguages();
        $rootLanguage = strtolower(trim($result['lang']));
        $pathLanguages = self::parsePathLanguages(
            $result[self::PATH_LANGUAGES_CONFIG_KEY] ?? ''
        );

        if (!in_array($rootLanguage, $projectLanguages, true)) {
            throw new Exception([
                'quiqqer/core',
                'exception.vhost.invalid.project.language',
                ['language' => $rootLanguage]
            ]);
        }

        $pathLanguages = array_values(
            array_filter(
                $pathLanguages,
                static fn(string $language): bool => $language !== $rootLanguage
            )
        );

        foreach ($pathLanguages as $pathLanguage) {
            if (in_array($pathLanguage, $projectLanguages, true)) {
                continue;
            }

            throw new Exception([
                'quiqqer/core',
                'exception.vhost.invalid.project.language',
                ['language' => $pathLanguage]
            ]);
        }

        $result['lang'] = $rootLanguage;
        $result[self::PATH_LANGUAGES_CONFIG_KEY] = implode(',', $pathLanguages);

        // A project language can have only one canonical VHost route.
        $config = $Config->toArray();
        $claimedLanguages = array_merge([$rootLanguage], $pathLanguages);

        foreach ($config as $h => $d) {
            if ($h === $vhost) {
                continue;
            }

            if (!is_array($d) || ($d['project'] ?? null) !== $result['project']) {
                continue;
            }

            $otherLanguages = self::parsePathLanguages(
                $d[self::PATH_LANGUAGES_CONFIG_KEY] ?? ''
            );

            if (!empty($d['lang']) && is_string($d['lang'])) {
                $otherLanguages[] = strtolower(trim($d['lang']));
            }

            foreach ($claimedLanguages as $claimedLanguage) {
                if (!in_array($claimedLanguage, $otherLanguages, true)) {
                    continue;
                }

                throw new Exception([
                    'quiqqer/core',
                    'exception.vhost.language.already.assigned',
                    [
                        'language' => $claimedLanguage,
                        'host' => (string)$h
                    ]
                ]);
            }
        }

        // Preserve legacy language host assignments while the installation is
        // migrated to root and path language ownership.
        $existing = $Config->getSection($vhost);

        if (
            is_array($existing)
            && ($existing['project'] ?? null) === $result['project']
        ) {
            foreach ($projectLanguages as $projectLanguage) {
                if (
                    array_key_exists($projectLanguage, $result)
                    || empty($existing[$projectLanguage])
                ) {
                    continue;
                }

                $result[$projectLanguage] = $existing[$projectLanguage];
            }
        }

        $Config->setSection($vhost, $result);
        $Config->save();

        $this->repair();
    }

    /**
     * Remove a vhost entry
     *
     *
     * @throws Exception
     */
    public function removeVhost(string $vhost): void
    {
        $Config = $this->getConfig();

        if (!$Config->existValue($vhost)) {
            throw new Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.vhost.not.found'
                )
            );
        }

        $Config->del($vhost);
        $Config->save();

        // clearing cache
        QUI\Cache\Manager::clear();
    }

    /**
     * Return the vhost data
     *
     * @return array<string, mixed>|bool
     *
     * @throws Exception
     */
    public function getVhost(string $vhost): bool|array
    {
        return $this->getConfig()->getSection($vhost);
    }

    /**
     * Return the Root- and Path-languages owned by a VHost.
     *
     * @return array<int, string>
     *
     * @throws Exception
     */
    public function getLanguagesByHost(string $vhost): array
    {
        $data = $this->getVhost($vhost);

        if (!is_array($data)) {
            return [];
        }

        $languages = self::parsePathLanguages(
            $data[self::PATH_LANGUAGES_CONFIG_KEY] ?? ''
        );

        if (!empty($data['lang']) && is_string($data['lang'])) {
            array_unshift($languages, strtolower(trim($data['lang'])));
        }

        return array_values(array_unique($languages));
    }

    /**
     * Return all hosts from the project
     *
     * @return array<int, string>
     *
     * @throws Exception
     */
    public function getHostsByProject(string $projectName): array
    {
        $config = $this->getList();
        $list = [];

        foreach ($config as $host => $data) {
            if (!isset($data['project'])) {
                continue;
            }

            if ($data['project'] == $projectName) {
                $list[] = $host;
            }
        }

        return $list;
    }

    /**
     * Get Project by VHost
     *
     * @throws Exception
     */
    public function getProjectByHost(string $vhost): bool|QUI\Projects\Project
    {
        foreach ($this->getList() as $host => $data) {
            if ($host !== $vhost) {
                continue;
            }

            return ProjectManager::getProject($data['project'], $data['lang']);
        }

        return false;
    }

    /**
     * Gets all domains which are registered in the system(config + VHost)
     *
     * @param bool $includeWWW - (optional) Should www. domains be added?
     *
     * @return array<int, string>
     *
     * @throws Exception
     */
    public function getRegisteredDomains(bool $includeWWW = false): array
    {
        $domains = [];

        // Get the host from the config
        $host = QUI::conf("globals", "host");

        if (is_string($host) && $host !== '') {
            $host = str_replace(['http://', 'https://'], '', $host);
            $host = rtrim($host, '/');

            if ($host !== '') {
                $domains[] = $host;
            }
        }

        // Get the domains from the vhosts
        $vhosts = QUI::vhosts();

        foreach ($vhosts as $key => $data) {
            if (empty($data['project'])) {
                continue;
            }

            $domains[] = $key;

            # Parse vhosts per language
            $projectName = $data['project'];
            $Project = QUI::getProject($projectName);
            $langs = $Project->getLanguages();

            foreach ($langs as $lang) {
                if (!empty($data[$lang])) {
                    $domains[] = $data[$lang];
                }
            }

            # Parse httpshost
            if (!empty($data['httpshost'])) {
                $domains[] = $data['httpshost'];
            }
        }

        if ($includeWWW) {
            foreach ($domains as $domain) {
                $domains[] = "www." . $domain;
            }
        }

        return array_unique($domains);
    }
}
