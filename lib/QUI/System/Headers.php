<?php

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
    protected $hsts = array(
        'max-age'    => '31536000',
        'subdomains' => false,
        'preload'    => false
    );

    /**
     * @var array
     */
    protected $csp = array();

    /**
     * @var bool
     */
    protected $cspLegacy = false;

    /**
     * List of csp directives
     *
     * @var array
     */
    protected $cspDirective = array(
        'base'    => 'base-uri',
        'child'   => 'child-src',
        'connect' => 'connect-src',
        'default' => 'default-src',
        'font'    => 'font-src',
        'form'    => 'form-action',
        'image'   => 'img-src',
        'img'     => 'img-src',
        'script'  => 'script-src',
        'style'   => 'style-src',
        'object'  => 'object-src',
        'report'  => 'report-uri'
    );

    /**
     * csp written out
     *
     * @var array
     */
    protected $cspSource = array(
        'none'           => "'none'",
        'self'           => "'self'",
        'strict-dynamic' => "'strict-dynamic'",
        'unsafe-inline'  => "'unsafe-inline'",
        'unsafe-eval'    => "'unsafe-eval'"
    );

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

        // default
        $this->cspAdd('self', 'script-src');
        $this->cspAdd('self', 'object-src');
        $this->cspAdd('unsafe-eval', 'script-src');
        $this->cspAdd('unsafe-inline', 'script-src');
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
            'max-age=' . $this->hsts['max-age']
            . ($this->hsts['subdomains'] ? '; includeSubDomains' : '')
            . ($this->hsts['preload'] ? '; preload' : '')
        );


        // @todo variable
        $Response->headers->set("X-Content-Type-Options", "nosniff");
        $Response->headers->set("X-XSS-Protection", "1; mode=block");
        $Response->headers->set("X-Frame-Options", "SAMEORIGIN");


        // create CSP header
        $list = array();
        $csp  = array();

        foreach ($this->csp as $entry) {
            $value = $entry['value'];

            if (isset($this->cspSource[$value])) {
                $value = $this->cspSource[$value];
            }

            $list[$entry['directive']][] = $value;
        }

        foreach ($list as $directive => $entries) {
            $csp[] = $directive . ' ' . implode(' ', $entries);
        }

        $Response->headers->set("Content-Security-Policy", implode('; ', $csp));
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
        if ($this->isDirectiveAllowed($directive) === false) {
            return;
        }

        // value cleanup
        $value = str_replace(
            array(';', '"', "'"),
            '',
            $value
        );

        $this->csp[] = array(
            'value'     => $value,
            'directive' => $directive
        );
    }

    /**
     * Remove csp header entry or entries
     *
     * @param string $cspValue
     */
    public function cspRemove($cspValue)
    {
        $new = array();

        foreach ($this->csp as $cspEntry) {
            if ($cspEntry['value'] != $cspValue) {
                $new[] = $cspEntry;
            }
        }

        $this->csp = $new;
    }

    /**
     * Content-Security-Policy
     *
     * @param bool $mode
     */
    public function cspLegacy($mode = true)
    {
        $this->cspLegacy = (bool)$mode;
    }

    /**
     * Is the directive allowed?
     *
     * @param string $directive
     * @return bool
     */
    public function isDirectiveAllowed($directive)
    {
        if (isset($this->cspDirective[$directive])) {
            return true;
        }

        foreach ($this->cspDirective as $d) {
            if ($d == $directive) {
                return true;
            }
        }

        return false;
    }
}
