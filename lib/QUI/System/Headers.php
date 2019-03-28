<?php

/**
 * This file contains QUI\System\Header
 */

namespace QUI\System;

use QUI;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class Headers
 * Header check: https://observatory.mozilla.org/
 *
 * @package QUI\System
 */
class Headers
{
    /**
     * @var Response
     */
    protected $Response = null;


    /**
     * Default HSTS settings
     *
     * @var array
     */
    protected $hsts = [
        'max-age'    => '31536000',
        'subdomains' => false,
        'preload'    => false
    ];

    /**
     * @var array
     */
    protected $csp = [];

    /**
     * Headers constructor.
     *
     * @param Response $Response
     */
    public function __construct($Response = null)
    {
        if ($Response) {
            $this->Response = $Response;
        } else {
            $this->Response = QUI::getGlobalResponse();
        }

        // default HSTS
        if (QUI::conf('securityHeaders_hsts', 'max_age')) {
            $this->hstsMaxAge(QUI::conf('securityHeaders_hsts', 'max_age'));
        }

        if (QUI::conf('securityHeaders_hsts', 'subdomains')) {
            $this->hstsSubdomains(true);
        }

        if (QUI::conf('securityHeaders_hsts', 'preload')) {
            $this->hstsPreload(true);
        }

        // default CSP
        $cspHeaders = QUI::conf('securityHeaders_csp');

        if (!empty($cspHeaders) && \is_array($cspHeaders)) {
            foreach ($cspHeaders as $key => $values) {
                $values = \explode(' ', $values);

                foreach ($values as $value) {
                    $this->cspAdd($value, $key);
                }
            }
        }
    }

    /**
     * Return the response object
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->Response;
    }

    /**
     * Set the headers to the request object
     */
    public function compile()
    {
        $Response = $this->getResponse();

        $Response->setCharset('UTF-8');
        $Response->headers->set('Content-Type', 'text/html');

        // HTTP Strict Transport Security (HSTS)
        $Response->headers->set(
            'Strict-Transport-Security',
            'max-age='.$this->hsts['max-age']
            .($this->hsts['subdomains'] ? '; includeSubDomains' : '')
            .($this->hsts['preload'] ? '; preload' : '')
        );


        // @todo variable
        $Response->headers->set("X-Content-Type-Options", "nosniff");
        $Response->headers->set("X-XSS-Protection", "1; mode=block");
//        $Response->headers->set("X-Frame-Options", "SAMEORIGIN");


        // create CSP header
        $list = [];
        $csp  = [];

        $cspSources    = CSP::getInstance()->getCSPSources();
        $cspDirectives = CSP::getInstance()->getCSPDirectives();

        foreach ($this->csp as $entry) {
            $value     = $entry['value'];
            $directive = $entry['directive'];

            if (isset($cspSources[$value])) {
                $value = $cspSources[$value];
            }

            if (isset($cspDirectives[$directive])) {
                $directive = $cspDirectives[$directive];
            }

            $list[$directive][] = $value;
        }

        foreach ($list as $directive => $entries) {
            $csp[] = $directive.' '.\implode(' ', $entries);
        }

        $Response->headers->set("Content-Security-Policy", \implode('; ', $csp));
    }

    /**
     * HTTP Strict Transport Security (HSTS)
     */

    /**
     * HTTP Strict Transport Security
     * Add parameter for the hsts header
     *
     * @param int $maxAge
     * @param bool $subDomains - for subdomains, too
     * @param bool $preload
     */
    public function hsts(
        $maxAge = 31536000,
        $subDomains = false,
        $preload = false
    ) {
        $this->hsts['max-age']    = (int)$maxAge;
        $this->hsts['subdomains'] = (bool)$subDomains;
        $this->hsts['preload']    = (bool)$preload;
    }

    /**
     * HTTP Strict Transport Security
     * subdomains param
     *
     * @param bool $mode
     */
    public function hstsSubdomains($mode = true)
    {
        $this->hsts['subdomains'] = (bool)$mode;
    }

    /**
     * HTTP Strict Transport Security
     * preload param
     *
     * @param bool $mode
     */
    public function hstsPreload($mode = true)
    {
        $this->hsts['preload'] = (bool)$mode;
    }

    /**
     * HTTP Strict Transport Security
     * max age param
     *
     * @param integer $maxAge
     */
    public function hstsMaxAge($maxAge)
    {
        $this->hsts['max-age'] = (int)$maxAge;
    }

    /**
     * Content-Security-Policy
     */

    /**
     * Add a Content-Security-Policy entry
     *
     * @param string $value - Domain or a csp value
     * @param string $directive - optional, (default = defaulf) CSP directive,
     *                            value can be an entry from the CSP directive list
     */
    public function cspAdd($value, $directive = 'default')
    {
        if (CSP::getInstance()->isDirectiveAllowed($directive) === false) {
            return;
        }

        // value cleanup
        $value = \str_replace(
            [';', '"', "'"],
            '',
            $value
        );

        $this->csp[] = [
            'value'     => $value,
            'directive' => $directive
        ];
    }

    /**
     * Remove csp header entry or entries
     *
     * @param string $cspValue
     */
    public function cspRemove($cspValue)
    {
        $new = [];

        foreach ($this->csp as $cspEntry) {
            if ($cspEntry['value'] != $cspValue) {
                $new[] = $cspEntry;
            }
        }

        $this->csp = $new;
    }
}
