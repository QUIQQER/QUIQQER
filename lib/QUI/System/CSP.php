<?php


namespace QUI\System;

use QUI;

/**
 * Class CSP
 * Content Security Policy Helper
 * Helps with the config
 *
 * Header check: https://observatory.mozilla.org/
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
        if (\is_null(self::$Instance)) {
            self::$Instance = new CSP();
        }

        return self::$Instance;
    }

    /**
     * List of csp directives
     *
     * @var array
     */
    protected $cspDirective = [
        'base'    => 'base-uri',
        'child'   => 'child-src',
        'connect' => 'connect-src',
        'default' => 'default-src',
        'font'    => 'font-src',
        'form'    => 'form-action',
        'image'   => 'image-src',
        'img'     => 'img-src',
        'script'  => 'script-src',
        'style'   => 'style-src',
        'object'  => 'object-src',
        'report'  => 'report-uri'
    ];

    /**
     * csp written out
     *
     * @var array
     */
    protected $cspSource = [
        'none'           => "'none'",
        'self'           => "'self'",
        'strict-dynamic' => "'strict-dynamic'",
        'unsafe-inline'  => "'unsafe-inline'",
        'unsafe-eval'    => "'unsafe-eval'"
    ];

    /**
     * Delete all CSP directive entries
     */
    public function clearCSPDirectives()
    {
        $this->getConfig()->del('securityHeaders_csp');
        $this->getConfig()->save();
    }

    /**
     * @return QUI\Config
     */
    protected function getConfig()
    {
        return QUI::getConfig('etc/conf.ini.php');
    }

    /**
     * Cleanups the CSP rules
     */
    public function cleanup()
    {
        $Config = $this->getConfig();
        $list   = $this->getCSPDirectiveConfig();

        $Config->del('securityHeaders_csp');

        foreach ($list as $directive => $value) {
            $Config->setValue('securityHeaders_csp', $directive, $value);
        }

        $Config->save();
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

        $values = \explode(' ', $value);
        $list   = [];

        foreach ($values as $value) {
            $value = \str_replace(
                [';', '"', "'"],
                '',
                $value
            );

            if (isset($this->cspSource[$value])) {
                $value = $this->cspSource[$value];
            }

            $list[] = $value;
        }

        $list   = \array_unique($list);
        $Config = $this->getConfig();

        $Config->setValue(
            'securityHeaders_csp',
            $directive,
            \implode(' ', $list)
        );

        $Config->save();
    }

    /**
     * @return array
     */
    public function getCSPDirectiveConfig()
    {
        $config = $this->getConfig()->toArray();
        $csp    = [];

        if (isset($config['securityHeaders_csp'])) {
            $csp = $config['securityHeaders_csp'];
        }

        $result = [];

        foreach ($csp as $directive => $value) {
            if (isset($this->cspDirective[$directive])) {
                $directive = $this->cspDirective[$directive];
            }

            $values = \explode(' ', $value);

            foreach ($values as $directiveValue) {
                $directiveValue = \str_replace(
                    [';', '"', "'"],
                    '',
                    $directiveValue
                );

                if (isset($this->cspSource[$directiveValue])) {
                    $directiveValue = $this->cspSource[$directiveValue];
                }

                $result[$directive][] = $directiveValue;
            }
        }

        // cleanup
        foreach ($result as $directive => $values) {
            $result[$directive] = \implode(' ', \array_unique($values));
        }

        return $result;
    }
}
