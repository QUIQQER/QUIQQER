<?php

/**
 * This file contains \QUI\System\VhostManager
 */

namespace QUI\System;

use QUI;
use QUI\Config;
use QUI\Exception;
use QUI\Projects\Manager as ProjectManager;
use QUI\Utils\Security\Orthos;

use function array_unique;
use function explode;
use function file_exists;
use function file_put_contents;
use function rtrim;
use function str_replace;
use function trim;

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
     * Add a vhost
     *
     * @param string $vhost - host name (eq: www.something.com)
     *
     * @return string - clean vhost
     * @throws Exception
     */
    public function addVhost($vhost)
    {
        if (str_contains($vhost, '://')) {
            $parts = explode('://', $vhost);
            $vhost = $parts[1];
        }

        $vhost = trim($vhost, '/');
        $Config = $this->getConfig();

        if ($Config->existValue($vhost)) {
            throw new Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.vhost.exist'
                )
            );
        }

        $Config->setSection($vhost, []);
        $Config->save();

        $this->repair();

        return $vhost;
    }

    /**
     * Return the config
     *
     * @return \QUI\Config
     * @throws QUI\Exception
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
     * @throws Exception
     */
    public function repair()
    {
        $Config = $this->getConfig();
        $list = $this->getList();

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
                $langs = $Project->getAttribute('langs');
            } catch (QUI\Exception $Exception) {
                QUI::getMessagesHandler()->addError($Exception->getMessage());
                continue;
            }

            foreach ($langs as $lang) {
                if (isset($data[$lang]) && !empty($data[$lang])) {
                    continue;
                }

                // repair language entry
                $Config->setValue(
                    $host,
                    $lang,
                    $this->getHostByProject(
                        $data['project'],
                        $lang
                    )
                );
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
     * @throws Exception
     */
    public function getList()
    {
        return $this->getConfig()->toArray();
    }

    /**
     * Return the host, is a host is set for a project
     *
     * @param string $projectName - Name of the project
     * @param string $projectLang - Language of the project (de, en, etc...)
     *
     * @return string
     * @throws Exception
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
     * Add or edit a vhost entry
     *
     * @param string $vhost - host name (eq: www.something.com)
     * @param array $data - data of the host
     *
     * @throws Exception
     */
    public function editVhost($vhost, array $data)
    {
        $Config = $this->getConfig();

        if (!$Config->existValue($vhost)) {
            throw new Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.vhost.not.found'
                )
            );
        }

        // daten prüfen
        $result = [];

        foreach ($data as $key => $value) {
            $key = Orthos::clear($key);

            $result[$key] = $value;
        }

        if (!isset($result["project"])) {
            throw new Exception([
                'quiqqer/quiqqer',
                'exception.vhost.missing.data.project'
            ]);
        }

        if (!isset($result["project"])) {
            throw new QUI\Exception([
                'quiqqer/quiqqer',
                'exception.vhost.missing.data.lang'
            ]);
        }

        // host already exist - quiqqer/quiqqer#752
        $config = $Config->toArray();

        foreach ($config as $h => $d) {
            if ($h === $vhost) {
                continue;
            }

            if (
                $d['project'] === $result['project']
                && $d['lang'] === $result['lang']
            ) {
                throw new Exception([
                    'quiqqer/quiqqer',
                    'exception.vhost.same.host.already.exists'
                ]);
            }
        }


        // lang hosts
        $Project = QUI::getProject($result['project']);
        $projectLangs = $Project->getAttribute('langs');
        $lang = $result['lang'];

        foreach ($projectLangs as $projectLang) {
            if ($projectLang === $lang) {
                continue;
            }

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

        if (empty($result[$lang])) {
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
     * @throws Exception
     */
    public function removeVhost($vhost)
    {
        $Config = $this->getConfig();

        if (!$Config->existValue($vhost)) {
            throw new Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
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
     * @return array|false
     *
     * @throws Exception
     */
    public function getVhost($vhost)
    {
        return $this->getConfig()->getSection($vhost);
    }

    /**
     * Return all hosts from the project
     *
     * @param string $projectName - Name of the project
     * @return array
     *
     * @throws Exception
     */
    public function getHostsByProject($projectName)
    {
        $config = $this->getList();
        $list = [];

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

    /**
     * Get Project by VHost
     *
     * @param string $vhost
     * @return QUI\Projects\Project|false - Project or false if no project not found
     * @throws Exception
     */
    public function getProjectByHost($vhost)
    {
        foreach ($this->getList() as $host => $data) {
            if ($host !== $vhost) {
                continue;
            }

            return ProjectManager::getProject($data['project'], $data['lang']);
        }

        return false;
    }

    /**
     * Gets all domains which are registered in the system(config + VHost)
     *
     * @param bool $includeWWW - (optional) Should www. domains be added?
     *
     * @return array
     * @throws Exception
     */
    public function getRegisteredDomains($includeWWW = false)
    {
        $domains = [];

        // Get the host from the config
        $host = QUI::conf("globals", "host");
        $host = str_replace("http://", "", $host);
        $host = str_replace("https://", "", $host);
        $host = rtrim($host, "/");
        $domains[] = $host;

        // Get the domains from the vhosts
        $vhosts = QUI::vhosts();

        foreach ($vhosts as $key => $data) {
            if (!isset($data['project']) || empty($data['project'])) {
                continue;
            }

            $domains[] = $key;

            # Parse vhosts per language
            $projectName = $data['project'];
            $Project = QUI::getProject($projectName);
            $langs = $Project->getLanguages();

            foreach ($langs as $lang) {
                if (isset($data[$lang]) && !empty($data[$lang])) {
                    $domains[] = $data[$lang];
                }
            }

            # Parse httpshost
            if (isset($data['httpshost']) && !empty($data['httpshost'])) {
                $domains[] = $data['httpshost'];
            }
        }

        if ($includeWWW) {
            foreach ($domains as $domain) {
                $domains[] = "www." . $domain;
            }
        }

        return array_unique($domains);
    }
}
