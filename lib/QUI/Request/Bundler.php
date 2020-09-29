<?php

/**
 * This file contains \QUI\Request\Bundler
 */

namespace QUI\Request;

use QUI;
use QUI\Utils\Security\Orthos;

/**
 * Class Bundler
 *
 * @author  www.pcsg.de (Henning Leutz)
 */
class Bundler
{
    /**
     * Files includes
     *
     * @var array
     */
    protected $includes = [];

    /**
     * Bundler constructor.
     */
    public function __construct()
    {
        QUI::getAjax();
    }

    /**
     * Read the $_REQUEST and create the response
     */
    public function response()
    {
        if (!isset($_REQUEST['quiqqerBundle'])) {
            return '';
        }

        $result   = [];
        $requests = $_REQUEST['quiqqerBundle'];

        foreach ($requests as $request) {
            try {
                $result[$request['rid']] = $this->parseRequest($request);
            } catch (\Exception $Exception) {
                $result[$request['rid']]['Exception'] = [
                    'message' => $Exception->getMessage(),
                    'code'    => $Exception->getCode(),
                    'type'    => \get_class($Exception)
                ];
            }
        }

        return \json_encode($result);
    }

    /**
     *
     * @param array $request
     *
     * @return array
     * @throws QUI\Exception
     */
    protected function parseRequest($request)
    {
        if (!isset($request['request'])) {
            throw new QUI\Exception('Bad Request', 400);
        }

        foreach ($request['params'] as $k => $value) {
            $request['params'][$k] = \json_decode($value, true);
        }

        $function = $request['request'];

        if (!\is_array($function)) {
            $function = [$function];
        }

        $result = [
            'maintenance' => QUI::conf('globals', 'maintenance') ? 1 : 0
        ];

        foreach ($function as $fun) {
            $this->includes($fun);
            $this->includesPackage($fun, $request);
            $this->includesProject($fun, $request);

            QUI::getAjax()::checkPermissions($fun);

            $data = QUI::getAjax()->callRequestFunction($fun, $request['params']);

            // session close -> performance
            QUI::getSession()->getSymfonySession()->save();

            // maintenance flag
            $result[$fun] = $data;
        }

        // messages
        $MessageHandler = QUI::getMessagesHandler();

        if ($MessageHandler) {
            $result['message_handler'] = $MessageHandler->getMessagesAsArray(QUI::getUserBySession());

            $MessageHandler->clear();
        }

        $result['jsCallbacks'] = QUI::getAjax()->getJsCallbacks();

        return $result;
    }

    /**
     * Include normal files
     *
     * @param string $function - name of the function
     */
    protected function includes($function)
    {
        if (\is_array($function)) {
            foreach ($function as $f) {
                $this->includes($f);
            }

            return;
        }

        if (isset($this->includes[$function])) {
            return;
        }

        // admin ajax
        $file = OPT_DIR.'quiqqer/quiqqer/admin/'.\str_replace('_', '/', $function).'.php';
        $file = Orthos::clearPath($file);
        $file = \realpath($file);

        $dir = OPT_DIR.'quiqqer/quiqqer/admin/';

        if (\strpos($file, $dir) !== false && \file_exists($file)) {
            require_once $file;

            $this->includes[$function] = $file;

            return;
        }


        $file = CMS_DIR.\str_replace('_', '/', $function).'.php';
        $file = Orthos::clearPath($file);
        $file = \realpath($file);

        if (\strpos($file, CMS_DIR) !== false && \file_exists($file)) {
            require_once $file;

            $this->includes[$function] = $file;

            return;
        }
    }

    /**
     * Include package files
     *
     * @param string $function - name of the function
     * @param array $request - Request data
     */
    protected function includesPackage($function, $request)
    {
        if (!isset($request['params']['package'])) {
            return;
        }

        if (\is_array($function)) {
            foreach ($function as $f) {
                $this->includesPackage($f, $request);
            }

            return;
        }

        if (isset($this->includes[$function])) {
            return;
        }

        $package = $request['params']['package'];
        $dir     = OPT_DIR;

        $firstpart = 'package_'.\str_replace('/', '_', $package);
        $ending    = \str_replace($firstpart, '', $function);

        $file = $dir.$package.\str_replace('_', '/', $ending).'.php';
        $file = Orthos::clearPath($file);
        $file = \realpath($file);

        if (\strpos($file, $dir) !== false && \file_exists($file)) {
            require_once $file;
        }

        $this->includes[$function] = $file;
    }

    /**
     * Include projects files
     *
     * @param string $function - name of the function
     * @param array $request - Request data
     *
     * @throws QUI\Exception
     */
    protected function includesProject($function, $request)
    {
        if (!isset($request['params']['project'])) {
            return;
        }

        if (\is_array($function)) {
            foreach ($function as $f) {
                $this->includesProject($f, $request);
            }

            return;
        }

        if (isset($this->includes[$function])) {
            return;
        }

        try {
            $Project = QUI::getProjectManager()->decode($request['params']['project']);
        } catch (QUI\Exception $Exception) {
            $Project = QUI::getProjectManager()->getProject(
                $request['params']['project']
            );
        }

        $projectDir = USR_DIR.$Project->getName();
        $firstpart  = 'project_'.$Project->getName().'_';

        $file = \str_replace($firstpart, '', $function);
        $file = $projectDir.'/lib/'.\str_replace('_', '/', $file).'.php';
        $file = Orthos::clearPath($file);
        $file = \realpath($file);

        $dir = $projectDir.'/lib/';

        if (\strpos($file, $dir) !== false && \file_exists($file)) {
            require_once $file;
        }

        $this->includes[$function] = $file;
    }
}
