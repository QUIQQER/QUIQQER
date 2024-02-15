<?php

/**
 * This file contains QUI\System\Header
 */

namespace QUI\System;

use QUI;
use Symfony\Component\HttpFoundation\Response;

use function explode;
use function implode;
use function is_array;
use function str_replace;

/**
 * Class Headers
 * Header check: https://observatory.mozilla.org/
 */
class Headers
{
    /**
     * @var Response|null
     */
    protected ?Response $Response = null;

    /**
     * Default HSTS settings
     *
     * @var array
     */
    protected array $hsts = [
        'max-age' => '31536000',
        'subdomains' => false,
        'preload' => false
    ];

    /**
     * @var array
     */
    protected array $csp = [];

    /**
     * @var bool
     */
    protected $xFrameOptions = false;

    /**
     * @var bool
     */
    protected $xContentTypeOptions = "nosniff";

    /**
     * @var bool
     */
    protected $xXSSProtection = "1; mode=block";

    /**
     * Headers constructor.
     *
     * @param Response|null $Response
     */
    public function __construct(?Response $Response = null)
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

        if (QUI::conf('securityHeaders', 'xFrameOptions')) {
            $this->xFrameOptions = QUI::conf('securityHeaders', 'xFrameOptions');
        }

        if (QUI::conf('securityHeaders', 'xContentTypeOptions')) {
            $this->xContentTypeOptions = QUI::conf('securityHeaders', 'xContentTypeOptions');
        }

        if (QUI::conf('securityHeaders', 'xXSSProtection')) {
            $this->xXSSProtection = QUI::conf('securityHeaders', 'xXSSProtection');
        }

        // default CSP
        $cspHeaders = QUI::conf('securityHeaders_csp');

        if (!empty($cspHeaders) && is_array($cspHeaders)) {
            foreach ($cspHeaders as $key => $values) {
                $values = explode(' ', $values);

                foreach ($values as $value) {
                    $this->cspAdd($value, $key);
                }
            }
        }
    }

    /**
     * HTTP Strict Transport Security
     * max age param
     *
     * @param integer $maxAge
     */
    public function hstsMaxAge(int $maxAge)
    {
        $this->hsts['max-age'] = $maxAge;
    }

    /**
     * HTTP Strict Transport Security
     * subdomains param
     *
     * @param bool $mode
     */
    public function hstsSubdomains(bool $mode = true)
    {
        $this->hsts['subdomains'] = $mode;
    }

    /**
     * HTTP Strict Transport Security (HSTS)
     */

    /**
     * HTTP Strict Transport Security
     * preload param
     *
     * @param bool $mode
     */
    public function hstsPreload(bool $mode = true)
    {
        $this->hsts['preload'] = $mode;
    }

    /**
     * Add a Content-Security-Policy entry
     *
     * @param string $value - Domain or a csp value
     * @param string $directive - optional, (default = default) CSP directive,
     *                            value can be an entry from the CSP directive list
     */
    public function cspAdd(string $value, string $directive = 'default')
    {
        if (CSP::getInstance()->isDirectiveAllowed($directive) === false) {
            return;
        }

        // value cleanup
        $value = str_replace(
            [';', '"', "'"],
            '',
            $value
        );

        $this->csp[] = [
            'value' => $value,
            'directive' => $directive
        ];
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

        $Response->headers->set("X-Content-Type-Options", $this->xContentTypeOptions);
        $Response->headers->set("X-XSS-Protection", $this->xXSSProtection);

        if ($this->xFrameOptions) {
            $Response->headers->set("X-Frame-Options", $this->xFrameOptions);
        }

        // create CSP header
        $list = [];
        $csp = [];

        $cspSources = CSP::getInstance()->getCSPSources();
        $cspDirectives = CSP::getInstance()->getCSPDirectives();

        foreach ($this->csp as $entry) {
            $value = $entry['value'];
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
            $csp[] = $directive . ' ' . implode(' ', $entries);
        }

        $Response->headers->set("Content-Security-Policy", implode('; ', $csp));
    }

    /**
     * Return the response object
     *
     * @return Response
     */
    public function getResponse(): ?Response
    {
        return $this->Response;
    }

    /**
     * Content-Security-Policy
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
        int $maxAge = 31_536_000,
        bool $subDomains = false,
        bool $preload = false
    ) {
        $this->hsts['max-age'] = $maxAge;
        $this->hsts['subdomains'] = $subDomains;
        $this->hsts['preload'] = $preload;
    }

    /**
     * Remove csp header entry or entries
     *
     * @param string $cspValue
     */
    public function cspRemove(string $cspValue)
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
