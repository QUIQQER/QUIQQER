<?php

/**
 * This class contains \QUI\System\Tests\ModRewrite
 */

namespace QUI\System\Tests;

use QUI;

/**
 * ModRewrite Test
 *
 * @package quiqqer/quiqqer
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

        $this->setAttributes(array(
            'title'       => 'Apache mod_rewrite installed',
            'description' => ''
        ));

        $this->isRequired = self::TEST_IS_REQUIRED;
    }

    /**
     * Check, if mod rewrite is enabled
     *
     * @return self::STATUS_OK|self::STATUS_ERROR
     */
    public function execute()
    {
        // quiqqer check
        if (array_key_exists('HTTP_MOD_REWRITE', $_SERVER)) {
            return self::STATUS_OK;
        }

        if (getenv('HTTP_MOD_REWRITE') == 'On') {
            return self::STATUS_OK;
        }

        // test with apache modules
        if (function_exists('apache_get_modules')
            && in_array('mod_rewrite', apache_get_modules())
        ) {
            return self::STATUS_OK;
        }

        // phpinfo test
        ob_start();
        phpinfo();
        $phpinfo = ob_get_contents();
        ob_end_clean();

        if (strpos('mod_rewrite', $phpinfo) !== false) {
            return self::STATUS_OK;
        }

        return $this->_checkViaHTTP();
    }

    /**
     * makes a http request and check, if the mod rewrite works
     *
     * @return self::STATUS_OK|self::STATUS_ERROR
     */
    protected function _checkViaHTTP()
    {
        try {
            $Project = QUI::getProjectManager()->getStandard();
            $host = $Project->getHost();

        } catch (QUI\Exception $Exception) {
            return self::STATUS_ERROR;
        }

        try {
            $result = QUI\Utils\Request\Url::get(
                'http://'.$host.'/'.URL_SYS_DIR
                .'/ajax.php?_rf=["ajax_system_modRewrite"]'
            );

        } catch (QUI\Exception $Exception) {
            return self::STATUS_ERROR;
        }


        if (strpos($result, '<quiqqer>') === false) {
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
