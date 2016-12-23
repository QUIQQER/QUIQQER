<?php

/**
 * This file contains \QUI\System\VhostManager
 */

namespace QUI\System;

use QUI;
use QUI\Config;
use QUI\Utils\Security\Orthos;

/**
 * Virtual Host Manager
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 *
 * @todo    vhosts permissions
 */
class VhostManager
{
    /**
     * Config
     *
     * @var \QUI\Config
     */
    protected $Config = null;

    /**
     * Return the config
     *
     * @return \QUI\Config
     */
    protected function getConfig()
    {
        if (!file_exists(ETC_DIR . 'vhosts.ini.php')) {
            file_put_contents(ETC_DIR . 'vhosts.ini.php', '');
        }

        $this->Config = new Config(ETC_DIR . 'vhosts.ini.php');

        return $this->Config;
    }

    /**
     * Check the vhosts entry and tries to repair it
     * eq. search empty language entries
     */
    public function repair()
    {
        $Config = $this->getConfig();
        $list   = $this->getList();

        // check lang entries
        foreach ($list as $host => $data) {
            if (!isset($data['project'])) {
                continue;
            }

            if (!isset($data['lang'])) {
                continue;
            }

            if (!isset($data['template'])) {
                continue;
            }

            try {
                $Project = \QUI::getProject($data['project']);
                $langs   = $Project->getAttribute('langs');
            } catch (QUI\Exception $Exception) {
                QUI::getMessagesHandler()->addError($Exception->getMessage());
                continue;
            }

            foreach ($langs as $lang) {
                if (isset($data[$lang]) && !empty($data[$lang])) {
                    continue;
                }

                // repair language entry
                $Config->setValue($host, $lang, $this->getHostByProject(
                    $data['project'],
                    $lang
                ));
            }
        }

        $Config->save();

        // clearing cache
        QUI\Cache\Manager::clear();
    }

    /**
     * Return the vhost list
     *
     * @return array
     */
    public function getList()
    {
        return $this->getConfig()->toArray();
    }

    /**
     * Add a vhost
     *
     * @param string $vhost - host name (eq: www.something.com)
     *
     * @return string - clean vhost
     * @throws \QUI\Exception
     */
    public function addVhost($vhost)
    {
        if (strpos($vhost, '://') !== false) {
            $parts = explode('://', $vhost);
            $vhost = $parts[1];
        }

        $vhost  = trim($vhost, '/');
        $Config = $this->getConfig();

        if ($Config->existValue($vhost)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.vhost.exist'
                )
            );
        }

        $Config->setSection($vhost, array());
        $Config->save();

        $this->repair();

        return $vhost;
    }

    /**
     * Add or edit a vhost entry
     *
     * @param string $vhost - host name (eq: www.something.com)
     * @param array $data - data of the host
     *
     * @throws \QUI\Exception
     */
    public function editVhost($vhost, array $data)
    {
        $Config = $this->getConfig();

        if (!$Config->existValue($vhost)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.vhost.not.found'
                )
            );
        }

        // daten prüfen
        $result = array();

        foreach ($data as $key => $value) {
            $key = Orthos::clear($key);

            $result[$key] = $value;
        }

        // lang hosts
        $Project      = QUI::getProject($result['project']);
        $projectLangs = $Project->getAttribute('langs');
        $lang         = $result['lang'];

        foreach ($projectLangs as $projectLang) {
            if (!isset($result[$projectLang])) {
                $result[$projectLang] = '';
            }

            if (!empty($result[$projectLang])) {
                continue;
            }

            $result[$projectLang] = $this->getHostByProject(
                $result['project'],
                $projectLang
            );
        }

        if (!isset($result[$lang]) || empty($result[$lang])) {
            $result[$lang] = $vhost;
        }

        $Config->setSection($vhost, $result);
        $Config->save();

        $this->repair();
    }

    /**
     * Remove a vhost entry
     *
     * @param string $vhost
     *
     * @throws \QUI\Exception
     */
    public function removeVhost($vhost)
    {
        $Config = $this->getConfig();

        if (!$Config->existValue($vhost)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.vhost.not.found'
                )
            );
        }

        $Config->del($vhost);
        $Config->save();

        // clearing cache
        QUI\Cache\Manager::clear();
    }

    /**
     * Return the vhost data
     *
     * @param string $vhost
     *
     * @return array|false
     */
    public function getVhost($vhost)
    {
        return $this->getConfig()->getSection($vhost);
    }

    /**
     * Return the host, is a host is set for a project
     *
     * @param string $projectName - Name of the project
     * @param string $projectLang - Language of the project (de, en, etc...)
     *
     * @return string
     */
    public function getHostByProject($projectName, $projectLang)
    {
        $config = $this->getList();

        foreach ($config as $host => $data) {
            if (!isset($data['project'])) {
                continue;
            }

            if (!isset($data['lang'])) {
                continue;
            }

            if ($data['project'] != $projectName) {
                continue;
            }

            if ($data['lang'] != $projectLang) {
                continue;
            }

            return $host;
        }

        return '';
    }

    /**
     * Return all hosts from the project
     *
     * @param string $projectName - Name of the project
     *
     * @return array
     */
    public function getHostsByProject($projectName)
    {
        $config = $this->getList();
        $list   = array();

        foreach ($config as $host => $data) {
            if (!isset($data['project'])) {
                continue;
            }

            if ($data['project'] == $projectName) {
                $list[] = $host;
            }
        }

        return $list;
    }
}
