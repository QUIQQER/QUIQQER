<?php


namespace QUI\System;

use QUI;

use function array_merge;
use function array_unique;
use function array_values;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function in_array;
use function is_null;
use function str_replace;

use const ETC_DIR;

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
     * @var ?CSP
     */
    protected static ?CSP $Instance = null;

    /**
     * List of csp directives
     *
     * @var array
     */
    protected array $allowedIni = [];

    /**
     * Return the global CSP object
     *
     * @return CSP
     */
    public static function getInstance(): ?CSP
    {
        if (is_null(self::$Instance)) {
            self::$Instance = new CSP();
        }

        return self::$Instance;
    }

    /**
     * List of csp directives
     * - default directives
     *
     * @var array
     */
    protected array $cspDirective = [
        'base'           => 'base-uri',
        'child'          => 'child-src',
        'connect'        => 'connect-src',
        'default'        => 'default-src',
        'font'           => 'font-src',
        'form'           => 'form-action',
        'image'          => 'img-src',
        'img'            => 'img-src',
        'script'         => 'script-src',
        'style'          => 'style-src',
        'object'         => 'object-src',
        'report'         => 'report-uri',
        'frameAncestors' => 'frame-ancestors',
        'ancestors'      => 'frame-ancestors',
        'reportUri'      => 'report-uri',
        'styleSrcElem'   => 'style-src-elem'
    ];

    /**
     * csp written out
     *
     * @var array
     */
    protected array $cspSource = [
        'none'           => "'none'",
        'self'           => "'self'",
        'strict-dynamic' => "'strict-dynamic'",
        'unsafe-inline'  => "'unsafe-inline'",
        'unsafe-eval'    => "'unsafe-eval'"
    ];

    public function __construct()
    {
        if (!file_exists(ETC_DIR . 'cspList.ini')) {
            $default = array_values($this->cspDirective);
            $default = array_unique($default);
            $default = implode("\n", $default);

            file_put_contents(
                ETC_DIR . 'cspList.ini',
                $default
            );
        }

        $this->allowedIni = explode("\n", file_get_contents(ETC_DIR . 'cspList.ini'));
    }

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
     * Returns all available CSP sources
     *
     * @return array
     */
    public function getCSPSources(): array
    {
        return $this->cspSource;
    }

    /**
     * Returns all allowed csp directives
     *
     * @return array
     */
    public function getAllowedCSPList(): array
    {
        $allowed = $this->allowedIni;
        $allowed = array_merge($allowed, array_values($this->cspDirective));
        $allowed = array_unique($allowed);

        sort($allowed);

        return $allowed;
    }

    /**
     * Return all available CSP directives
     *
     * @return array
     */
    public function getCSPDirectives(): array
    {
        return $this->cspDirective;
    }

    /**
     * Is the directive allowed?
     *
     * @param string $directive
     * @return bool
     */
    public function isDirectiveAllowed(string $directive): bool
    {
        if (isset($this->cspDirective[$directive])) {
            return true;
        }

        if (in_array($directive, $this->cspDirective)) {
            return true;
        }

        if (in_array($directive, $this->allowedIni)) {
            return true;
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
        $list   = [];

        foreach ($values as $value) {
            $value = str_replace(
                [';', '"', "'"],
                '',
                $value
            );

            if (isset($this->cspSource[$value])) {
                $value = $this->cspSource[$value];
            }

            $list[] = $value;
        }

        $list   = array_unique($list);
        $Config = $this->getConfig();

        $Config->setValue(
            'securityHeaders_csp',
            $directive,
            implode(' ', $list)
        );

        $Config->save();
    }

    /**
     * @return array
     */
    public function getCSPDirectiveConfig(): array
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

            $values = explode(' ', $value);

            foreach ($values as $directiveValue) {
                $directiveValue = str_replace(
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
            $result[$directive] = implode(' ', array_unique($values));
        }

        return $result;
    }
}
