<?php

/**
 * This file contains \QUI\Projects\Site\Canonical
 */

namespace QUI\Projects\Site;

use QUI;

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
     * @var \QUI\Projects\Site
     */
    protected $Site;

    /**
     * construct
     *
     * @param \QUI\Projects\Site $Site
     */
    public function __construct($Site)
    {
        $this->Site = $Site;
    }

    /**
     * Return the meta tag, if it is allowed
     *
     * @return string
     */
    public function output()
    {
        $Site    = $this->Site;
        $Project = $Site->getProject();

        // host check
        if (isset($_SERVER['HTTP_HOST'])) {
            $requestHost          = $_SERVER['HTTP_HOST'];
            $hostWithoutProtocoll = $Project->getVHost(false, true);
            $httpsHost            = $Project->getVHost(true, true);

            if ($requestHost != $hostWithoutProtocoll) {
                return $this->getLinkRel($httpsHost . $this->Site->getCanonical());
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
                return $this->getLinkRel($httpsHost . $this->Site->getCanonical());
            }

            return '';
        }

        $requestUrl = '';

        if (isset($_REQUEST['_url'])) {
            $requestUrl = $_REQUEST['_url'];
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

        // canonical and request the same? than no output
        if ($httpsHost . URL_DIR . $requestUrl == $canonical) {
            return '';
        }


        // fix doppelter HOST  im canonical https://dev.quiqqer.com/quiqqer/quiqqer/issues/574

        if (strpos($canonical, 'http:') !== false || strpos($canonical, 'http:') !== false) {
            return $canonical;
        }

        return $this->getLinkRel($httpsHost . URL_DIR . $canonical);
    }

    /**
     * Return <link rel="canonical"> tag
     *
     * @param string $url - href link
     *
     * @return string
     */
    protected function getLinkRel($url)
    {
        return '<link rel="canonical" href="' . $url . '" />';
    }
}
