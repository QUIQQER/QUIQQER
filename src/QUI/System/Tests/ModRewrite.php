<?php

/**
 * This class contains \QUI\System\Tests\ModRewrite
 */

namespace QUI\System\Tests;

use QUI;

use function apache_get_modules;
use function array_key_exists;
use function function_exists;
use function getenv;
use function in_array;
use function json_decode;
use function ob_end_clean;
use function ob_get_contents;
use function ob_start;
use function phpinfo;
use function strpos;
use function substr;

/**
 * ModRewrite Test
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class ModRewrite extends QUI\System\Test
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setAttributes([
            'title' => 'Apache mod_rewrite installed',
            'description' => ''
        ]);

        $this->isRequired = self::TEST_IS_REQUIRED;
    }

    /**
     * Check, if mod rewrite is enabled
     *
     * @return int self::STATUS_OK|self::STATUS_ERROR
     */
    public function execute(): int
    {
        // quiqqer check
        if (array_key_exists('HTTP_MOD_REWRITE', $_SERVER)) {
            return self::STATUS_OK;
        }

        if (getenv('HTTP_MOD_REWRITE') == 'On') {
            return self::STATUS_OK;
        }

        // test with apache modules
        if (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())) {
            return self::STATUS_OK;
        }

        // phpinfo test
        ob_start();
        phpinfo();
        $phpinfo = ob_get_contents();
        ob_end_clean();

        if (str_contains('mod_rewrite', $phpinfo)) {
            return self::STATUS_OK;
        }

        return $this->checkViaHTTP();
    }

    /**
     * makes a http request and check, if the mod rewrite works
     *
     * @return int self::STATUS_OK|self::STATUS_ERROR
     */
    protected function checkViaHTTP(): int
    {
        try {
            $Project = QUI::getProjectManager()->getStandard();
            $host = $Project->getHost();
        } catch (QUI\Exception) {
            return self::STATUS_ERROR;
        }

        try {
            $result = QUI\Utils\Request\Url::get(
                'http://' . $host . '/' . URL_SYS_DIR
                . '/ajax.php?_rf=["ajax_system_modRewrite"]'
            );
        } catch (QUI\Exception) {
            return self::STATUS_ERROR;
        }


        if (!str_contains($result, '<quiqqer>')) {
            return self::STATUS_ERROR;
        }

        $start = 9;
        $end = strpos($result, '</quiqqer>');

        $quiqqer = substr($result, $start, $end - $start);
        $quiqqer = json_decode($quiqqer, true);

        if (!isset($quiqqer['ajax_system_modRewrite'])) {
            return self::STATUS_ERROR;
        }

        if ($quiqqer['ajax_system_modRewrite']) {
            return self::STATUS_OK;
        }

        return self::STATUS_ERROR;
    }
}
