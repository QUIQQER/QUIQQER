<?php


namespace QUI\System;

use QUI;

/**
 * Class CSP
 * Content Security Policy Helper
 * Helps with the config
 *
 * Header check: https://observatory.mozilla.org/
 *
 * @package QUI\System
 */
class CSP
{
    /**
     * @var CSP
     */
    protected static $Instance = null;

    /**
     * Return the global CSP object
     *
     * @return CSP
     */
    public static function getInstance()
    {
        if (is_null(self::$Instance)) {
            self::$Instance = new CSP();
        }

        return self::$Instance;
    }

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
     * Delete all CSP directive entries
     */
    public function clearCSPDirectives()
    {
        QUI::getConfig('etc/conf.ini.php')->del('securityHeaders_csp');
        QUI::getConfig('etc/conf.ini.php')->save();
    }

    /**
     * Return all available CSP sources
     * @return array
     */
    public function getCSPSources()
    {
        return $this->cspSource;
    }

    /**
     * Return all available CSP directives
     * @return array
     */
    public function getCSPDirectives()
    {
        return $this->cspDirective;
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

    /**
     * Save the directive
     *
     * @param $directive
     * @param $value
     *
     * @throws QUI\Exception
     */
    public function setCSPDirectiveToConfig($directive, $value)
    {
        if (!$this->isDirectiveAllowed($directive)) {
            throw new QUI\Exception('Directive is not allowed');
        }

        if (isset($this->cspDirective[$directive])) {
            $directive = $this->cspDirective[$directive];
        }

        $values = explode(' ', $value);
        $list   = array();

        foreach ($values as $value) {
            $value = str_replace(
                array(';', '"', "'"),
                '',
                $value
            );

            if (isset($this->cspSource[$value])) {
                $value = $this->cspSource[$value];
            }

            $list[] = $value;
        }

        $list = array_unique($list);

        QUI::getConfig('etc/conf.ini.php')->setValue(
            'securityHeaders_csp',
            $directive,
            implode(' ', $list)
        );

        QUI::getConfig('etc/conf.ini.php')->save();
    }
}
