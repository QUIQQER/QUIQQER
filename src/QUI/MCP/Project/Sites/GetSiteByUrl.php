<?php

/**
 * This file contains the \QUI\MCP\Project\Sites\GetSiteByUrl
 */

namespace QUI\MCP\Project\Sites;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use QUI\Projects\Project;
use QUI\Projects\Site;
use Throwable;

use function array_shift;
use function count;
use function explode;
use function fnmatch;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function ltrim;
use function mb_strlen;
use function mb_substr;
use function parse_url;
use function preg_match;
use function rtrim;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function str_replace;
use function strtolower;
use function trim;
use function urldecode;

use const FNM_CASEFOLD;
use const PHP_URL_FRAGMENT;
use const PHP_URL_HOST;
use const PHP_URL_PATH;
use const PHP_URL_QUERY;
use const URL_DIR;

class GetSiteByUrl extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $url,
                bool | null $load = null
            ): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $result = self::resolveUrl($url);
                    $Site = $result['site'];

                    if ($load === true) {
                        $Site->load();
                    }

                    return [
                        'inputUrl' => $url,
                        'host' => $result['host'],
                        'path' => $result['path'],
                        'matchedBy' => $result['matchedBy'],
                        'project' => self::parseProject($result['project']),
                        'site' => self::parseSite($Site, true)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_sites_get_by_url',
            description: 'Resolves a public QUIQQER URL to its project, language and site data.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['url'],
                'properties' => [
                    'url' => ['type' => 'string', 'description' => 'Absolute or relative site URL.'],
                    'load' => ['type' => 'boolean', 'default' => false]
                ]
            ]
        );
    }

    /**
     * @return array{host: string, path: string, matchedBy: string, project: Project, site: Site}
     *
     * @throws QUI\Exception
     */
    protected static function resolveUrl(string $url): array
    {
        $normalized = self::normalizeUrl($url);
        $path = self::removeUrlDir($normalized['path']);
        $segments = self::splitPath($path);
        $projectName = '';
        $template = false;
        $matchedBy = 'standard';

        if (isset($segments[0]) && str_starts_with($segments[0], QUI\Rewrite::URL_PROJECT_CHARACTER)) {
            $projectPrefix = str_replace(
                QUI\Rewrite::getDefaultSuffix(),
                '',
                mb_substr(array_shift($segments), 1)
            );

            if (str_contains($projectPrefix, QUI\Rewrite::URL_PROJECT_CHARACTER)) {
                $projectParts = explode(QUI\Rewrite::URL_PROJECT_CHARACTER, $projectPrefix, 2);
                $projectName = $projectParts[0];
                $template = $projectParts[1] ?? false;
            } else {
                $projectName = $projectPrefix;
            }

            $matchedBy = 'project-prefix';
        }

        $vhostData = self::getVhostData($normalized['host']);
        $lang = $vhostData['lang'] ?? null;

        if ($projectName === '' && isset($vhostData['project'])) {
            $projectName = (string)$vhostData['project'];
            $template = $vhostData['template'] ?? false;
            $matchedBy = 'vhost';
        }

        if (isset($segments[0]) && self::isLanguageSegment($segments[0])) {
            $lang = array_shift($segments);
        }

        if ($projectName !== '') {
            $Project = QUI::getProject($projectName, $lang ?: false, $template);
        } else {
            if (!self::isDefaultHost($normalized['host'])) {
                throw new QUI\Exception('No QUIQQER project found for URL host: ' . $normalized['host']);
            }

            $Project = QUI::getProjectManager()->getStandard();
        }

        $sitePath = implode('/', $segments);
        $Site = self::resolveSitePath($Project, $sitePath);

        return [
            'host' => $normalized['host'],
            'path' => $sitePath,
            'matchedBy' => $matchedBy,
            'project' => $Project,
            'site' => $Site
        ];
    }

    /**
     * @return array{host: string, path: string}
     */
    protected static function normalizeUrl(string $url): array
    {
        $url = trim($url);

        if (
            !preg_match('~^[a-z][a-z0-9+.-]*://~i', $url)
            && preg_match('~^[^/?#]+\.[^/?#]+(/|$)~', $url)
            && !self::isRelativeSiteFile($url)
        ) {
            $url = 'https://' . $url;
        }

        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);

        if (!is_string($host)) {
            $host = '';
        }

        if (!is_string($path)) {
            $path = '';
        }

        $query = parse_url($url, PHP_URL_QUERY);
        $fragment = parse_url($url, PHP_URL_FRAGMENT);

        if (is_string($query) && $query !== '') {
            $path .= '?' . $query;
        }

        if (is_string($fragment) && $fragment !== '') {
            $path .= '#' . $fragment;
        }

        return [
            'host' => strtolower($host),
            'path' => urldecode(parse_url($path, PHP_URL_PATH) ?: '')
        ];
    }

    protected static function isRelativeSiteFile(string $url): bool
    {
        $firstSegment = explode('/', $url, 2)[0] ?? $url;
        $firstSegment = explode('?', $firstSegment, 2)[0] ?? $firstSegment;
        $firstSegment = explode('#', $firstSegment, 2)[0] ?? $firstSegment;

        foreach ([QUI\Rewrite::getDefaultSuffix(), '.print', '.pdf'] as $suffix) {
            if ($suffix !== '' && str_ends_with($firstSegment, $suffix)) {
                return true;
            }
        }

        return false;
    }

    protected static function removeUrlDir(string $path): string
    {
        $path = ltrim($path, '/');
        $urlDir = trim(URL_DIR, '/');

        if ($urlDir !== '' && ($path === $urlDir || str_starts_with($path, $urlDir . '/'))) {
            return ltrim(mb_substr($path, mb_strlen($urlDir)), '/');
        }

        return $path;
    }

    /**
     * @return list<string>
     */
    protected static function splitPath(string $path): array
    {
        $path = trim($path, '/');

        if ($path === '') {
            return [];
        }

        return explode('/', $path);
    }

    protected static function isLanguageSegment(string $segment): bool
    {
        $segment = str_replace(QUI\Rewrite::getDefaultSuffix(), '', $segment);

        return mb_strlen($segment) === 2
            && in_array($segment, QUI::availableLanguages(), true);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getVhostData(string $host): array
    {
        if ($host === '') {
            return [];
        }

        $vhosts = QUI::vhosts();

        if (isset($vhosts[$host]) && is_array($vhosts[$host])) {
            return $vhosts[$host];
        }

        foreach ($vhosts as $vhost => $vhostData) {
            if (!is_string($vhost) || !str_contains($vhost, '*') || !is_array($vhostData)) {
                continue;
            }

            if (fnmatch($vhost, $host, FNM_CASEFOLD)) {
                return $vhostData;
            }
        }

        return [];
    }

    protected static function isDefaultHost(string $host): bool
    {
        if ($host === '') {
            return true;
        }

        $globalHost = (string)QUI::conf('globals', 'host');
        $globalHost = strtolower(str_replace(['http://', 'https://'], '', rtrim($globalHost, '/')));

        if ($globalHost !== '' && $globalHost === $host) {
            return true;
        }

        if (!empty(QUI::vhosts())) {
            return false;
        }

        return true;
    }

    /**
     * @throws QUI\Exception
     */
    protected static function resolveSitePath(Project $Project, string $path): Site
    {
        $path = trim($path, '/');

        if ($path === '') {
            return $Project->firstChild();
        }

        try {
            return self::resolveSiteByTreePath($Project, $path);
        } catch (Throwable) {
            $Site = QUI::getRewrite()->existRegisterPath($path, $Project);

            if ($Site instanceof Site) {
                return $Site;
            }

            throw new QUI\Exception('No QUIQQER site found for URL path: ' . $path);
        }
    }

    /**
     * @throws QUI\Exception
     */
    protected static function resolveSiteByTreePath(Project $Project, string $path): Site
    {
        $segments = self::splitPath($path);
        $Child = $Project->firstChild();
        $lastIndex = count($segments) - 1;

        foreach ($segments as $index => $segment) {
            if ($index === $lastIndex) {
                $segment = self::normalizeLastPathSegment($segment);
            }

            if ($segment === '') {
                continue;
            }

            $id = $Child->getChildIdByName($segment);
            $Child = $Project->get((int)$id);
        }

        return $Child;
    }

    protected static function normalizeLastPathSegment(string $segment): string
    {
        foreach ([QUI\Rewrite::getDefaultSuffix(), '.print', '.pdf'] as $suffix) {
            if ($suffix !== '' && str_ends_with($segment, $suffix)) {
                $segment = mb_substr($segment, 0, mb_strlen($suffix) * -1);
                break;
            }
        }

        return explode(QUI\Rewrite::URL_PARAM_SEPARATOR, $segment)[0] ?? '';
    }
}
