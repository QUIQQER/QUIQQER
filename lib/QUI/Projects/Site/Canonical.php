<?php

/**
 * This file contains \QUI\Projects\Site\Canonical
 */

namespace QUI\Projects\Site;

use QUI;

use function ltrim;
use function parse_url;
use function strpos;

/**
 * Canonical meta helper
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Canonical
{
    /**
     * Internal Site
     *
     * @var QUI\Interfaces\Projects\Site
     */
    protected QUI\Interfaces\Projects\Site $Site;

    /**
     * @var bool
     */
    protected bool $considerGetParams = false;

    /**
     * construct
     *
     * @param QUI\Interfaces\Projects\Site $Site
     */
    public function __construct(QUI\Interfaces\Projects\Site $Site)
    {
        $this->Site = $Site;
    }

    /**
     * Return the meta tag, if it is allowed
     *
     * @return string
     */
    public function output(): string
    {
        $Site    = $this->Site;
        $Project = $Site->getProject();

        $siteUrl = $this->Site->getCanonical();
        $siteUrl = $this->removeHost($siteUrl);

        // host check
        if (isset($_SERVER['HTTP_HOST'])) {
            $requestHost         = $_SERVER['HTTP_HOST'];
            $hostWithoutProtocol = $Project->getVHost(false, true);
            $httpsHost           = $Project->getVHost(true, true);

            if ($requestHost != $hostWithoutProtocol) {
                return $this->getLinkRel($httpsHost . $siteUrl);
            }
        }


        if ($this->Site->getId() === 1) {
            $httpsHost       = $Project->getVHost(true, true);
            $httpsHostExists = false;

            if (strpos($httpsHost, 'https:') !== false) {
                $httpsHostExists = true;
            }

            if ($httpsHostExists
                && QUI\Utils\System::isProtocolSecure() === false
            ) {
                return $this->getLinkRel($httpsHost . $siteUrl);
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

        $httpsHostExists = false;

        if (strpos($httpsHost, 'https:') !== false) {
            $httpsHostExists = true;
        }

        if (empty($canonical) || $canonical == $requestUrl) {
            // check if https host exist,
            // if true, and request ist not https, canonical is https
            if ($httpsHostExists
                && QUI\Utils\System::isProtocolSecure() === false
            ) {
                return $this->getLinkRel($httpsHost . URL_DIR . $requestUrl);
            }

            return '';
        }

        // canonical and request the same? then no output
        if ($httpsHost . URL_DIR . $requestUrl == $canonical && $this->considerGetParams === false) {
            return '';
        }


        // fix doppelter HOST im canonical https://dev.quiqqer.com/quiqqer/quiqqer/issues/574
        if (strpos($canonical, 'https:') !== false || strpos($canonical, 'http:') !== false) {
            return $this->getLinkRel($canonical);
        }

        return $this->getLinkRel($httpsHost . URL_DIR . $canonical);
    }

    /**
     * @param $url
     * @return array|false|int|string|null
     */
    protected function removeHost($url)
    {
        return parse_url($url, PHP_URL_PATH);
    }

    /**
     * Return <link rel="canonical"> tag
     *
     * @param string $url - href link
     *
     * @return string
     */
    protected function getLinkRel(string $url): string
    {
        return '<link rel="canonical" href="' . $url . '" />';
    }

    /**
     * Consider get Parameter at the canonical request check
     */
    public function considerGetParameterOn()
    {
        $this->considerGetParams = true;
    }

    /**
     * Get parameters are not considered at the request check
     */
    public function considerGetParameterOff()
    {
        $this->considerGetParams = false;
    }
}
