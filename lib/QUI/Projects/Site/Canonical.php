<?php

/**
 * This file contains \QUI\Projects\Site\Canonical
 */

namespace QUI\Projects\Site;

/**
 * Canonical meta helper
 *
 * @author www.pcsg.de (Henning Leutz)
 */

class Canonical
{
    /**
     * Internal Site
     * @var \QUI\Projects\Site
     */
    protected $_Site;

    /**
     * construct
     * @param \QUI\Projects\Site $Site
     */
    public function __construct($Site)
    {
        $this->_Site = $Site;
    }

    /**
     * Return the meta tag, if it is allowed
     *
     * @return String
     */
    public function output()
    {
        if ( $this->_Site->getId() === 1 ) {
            return '';
        }

        $requestUrl = '';

        if ( isset( $_REQUEST['_url'] ) ) {
            $requestUrl = $_REQUEST['_url'];
        }

        if ( empty( $requestUrl ) ) {
            return '';
        }

        $Site    = $this->_Site;
        $Project = $Site->getProject();

        $canonical = $this->_Site->getCanonical();
        $httpsHost = $Project->getVHost( true, true );

        $httpsHostExists = false;

        if ( strpos( $httpsHost , 'https:' ) !== false ) {
            $httpsHostExists = true;
        }


        if ( empty( $canonical ) || $canonical == $requestUrl )
        {
            // check if https host exist,
            // if true, and request ist not https, canonical is https
            if ( $httpsHostExists && \QUI\Utils\System::isProtocolHTTPS() === false ) {
                return $this->_getLinkRel( $httpsHost . URL_DIR . $requestUrl );
            }

            return '';
        }

        // canonical and request the same? than no output
        if ( $httpsHost . URL_DIR . $requestUrl == $canonical ) {
            return '';
        }

        return $this->_getLinkRel( $httpsHost . URL_DIR . $canonical );
    }

    /**
     * Return <link rel="canonical"> tag
     *
     * @param String $url - href link
     * @return string
     */
    protected function _getLinkRel($url)
    {
        return '<link rel="canonical" href="'. $url .'" />';
    }

}