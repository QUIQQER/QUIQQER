<?php

/**
 * This file contains \QUI\Projects\Site\Canonical
 */

namespace QUI\Projects\Site;

use QUI;
use QUI\Exception;

use function ltrim;
use function parse_url;
use function urldecode;

use const URL_DIR;

/**
 * Canonical meta helper
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Canonical
{
    protected bool $considerGetParams = true;

    public function __construct(
        protected QUI\Interfaces\Projects\Site $Site
    ) {
    }

    /**
     * Return the meta tag, if it is allowed
     */
    public function output(): string
    {
        $Site = $this->Site;
        $Project = $Site->getProject();

        $siteUrl = $this->Site->getCanonical();
        $siteUrl = $this->removeHost($siteUrl);

        if ($Site->getAttribute('meta.canonical') && $Site->getAttribute('meta.canonical') !== '') {
            $metaCanonical = $Site->getAttribute('meta.canonical');

            if (!QUI\Projects\Site\Utils::isSiteLink($metaCanonical)) {
                if (filter_var($metaCanonical, FILTER_VALIDATE_URL)) {
                    return $this->getLinkRel($metaCanonical);
                }

                return '';
            }
        }

        // host check
        $requestHost = $_SERVER['HTTP_HOST'] ?? null;

        if ($requestHost) {
            $hostWithoutProtocol = $Project->getVHost(false, true);
            $httpsHost = $Project->getVHost(true, true);

            if ($requestHost != $hostWithoutProtocol) {
                $httpsHost = rtrim($httpsHost, '/') . '/';
                return $this->getLinkRel($httpsHost . $siteUrl);
            }
        }


        if ($this->Site->getId() === 1) {
            $httpsHost = $Project->getVHost(true, true);
            $httpsHostExists = false;

            if (str_contains($httpsHost, 'https:')) {
                $httpsHostExists = true;
            }

            if (
                $httpsHostExists
                && QUI\Utils\System::isProtocolSecure() === false
            ) {
                return $this->getLinkRel($httpsHost . $siteUrl);
            }

            $canonical = $this->Site->getCanonical();

            if (str_contains($canonical, 'https:') === false || str_contains($canonical, 'http:') === false) {
                $canonical = $httpsHost . $canonical;
            }

            if ($this->Site->getAttribute('ERROR_HEADER')) {
                return $this->getLinkRel($canonical);
            }

            if (str_contains($_REQUEST['_url'], QUI\Rewrite::URL_PARAM_SEPARATOR)) {
                return $this->getLinkRel($canonical);
            }

            return '';
        }

        $requestUrl = '';

        if (isset($_REQUEST['_url'])) {
            $requestUrl = $_REQUEST['_url'];
        }

        if ($this->considerGetParams) {
            $requestUrl = ltrim($_SERVER['REQUEST_URI'], '/');
        }

        if (empty($requestUrl)) {
            return '';
        }

        $canonical = ltrim($this->Site->getCanonical(), '/');
        $httpsHost = $Project->getVHost(true, true);

        if (QUI\Projects\Site\Utils::isSiteLink($canonical)) {
            try {
                $CanonicalSite = QUI\Projects\Site\Utils::getSiteByLink($canonical);
                $canonical = $CanonicalSite->getUrlRewritten();
                $canonical = ltrim($canonical, '/');
            } catch (\Exception) {
            }
        }

        $httpsHostExists = false;

        if (str_contains($httpsHost, 'https:')) {
            $httpsHostExists = true;
        }

        if (empty($canonical) || $canonical == $requestUrl) {
            // check if https host exist,
            // if true, and request ist not https, canonical is https
            if (
                $httpsHostExists
                && QUI\Utils\System::isProtocolSecure() === false
            ) {
                return $this->getLinkRel($httpsHost . URL_DIR . $requestUrl);
            }

            return '';
        }

        // canonical and request the same? then no output
        $urlIsTheSame = urldecode($httpsHost . URL_DIR . $requestUrl) == $canonical
            || $httpsHost . URL_DIR . $requestUrl == $canonical;

        if ($urlIsTheSame && $this->considerGetParams === false) {
            return '';
        }


        // fix doppelter HOST im canonical https://dev.quiqqer.com/quiqqer/core/issues/574
        if (str_contains($canonical, 'https:') || str_contains($canonical, 'http:')) {
            return $this->getLinkRel($canonical);
        }

        return $this->getLinkRel($httpsHost . URL_DIR . $canonical);
    }

    /**
     * @param $url
     * @return array|false|int|string|null
     */
    protected function removeHost($url): bool | int | array | string | null
    {
        return parse_url($url, PHP_URL_PATH);
    }

    /**
     * Return <link rel="canonical"> tag
     */
    protected function getLinkRel(string $url): string
    {
        return '<link rel="canonical" href="' . $url . '" />';
    }

    /**
     * Consider get Parameter at the canonical request check
     */
    public function considerGetParameterOn(): void
    {
        $this->considerGetParams = true;
    }

    /**
     * Get parameters are not considered at the request check
     */
    public function considerGetParameterOff(): void
    {
        $this->considerGetParams = false;
    }
}
