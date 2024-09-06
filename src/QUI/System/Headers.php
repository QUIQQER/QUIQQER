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
    protected ?Response $Response = null;

    /**
     * Default HSTS settings
     */
    protected array $hsts = [
        'max-age' => '31536000',
        'subdomains' => false,
        'preload' => false
    ];

    protected array $csp = [];

    protected string|array|bool $xFrameOptions = false;

    protected string|array|bool $xContentTypeOptions = "nosniff";

    protected string|array|bool $xXSSProtection = "1; mode=block";

    /**
     * Headers constructor.
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
            $this->hstsSubdomains();
        }

        if (QUI::conf('securityHeaders_hsts', 'preload')) {
            $this->hstsPreload();
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

        if (empty($cspHeaders)) {
            return;
        }

        if (!is_array($cspHeaders)) {
            return;
        }

        foreach ($cspHeaders as $key => $values) {
            $values = explode(' ', $values);

            foreach ($values as $value) {
                $this->cspAdd($value, $key);
            }
        }
    }

    /**
     * HTTP Strict Transport Security
     * max age param
     */
    public function hstsMaxAge(int $maxAge): void
    {
        $this->hsts['max-age'] = $maxAge;
    }

    /**
     * HTTP Strict Transport Security
     * subdomains param
     */
    public function hstsSubdomains(bool $mode = true): void
    {
        $this->hsts['subdomains'] = $mode;
    }

    /**
     * HTTP Strict Transport Security (HSTS)
     */
    /**
     * HTTP Strict Transport Security
     * preload param
     */
    public function hstsPreload(bool $mode = true): void
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
    public function cspAdd(string $value, string $directive = 'default'): void
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
    public function compile(): void
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
        $status = QUI::conf('securityHeaders_csp', 'status');

        if ($status === false || (int)$status === 1) {
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
    }

    /**
     * Return the response object
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
    ): void {
        $this->hsts['max-age'] = $maxAge;
        $this->hsts['subdomains'] = $subDomains;
        $this->hsts['preload'] = $preload;
    }

    /**
     * Remove csp header entry or entries
     */
    public function cspRemove(string $cspValue): void
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
