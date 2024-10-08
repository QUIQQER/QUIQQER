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
    protected ?Config $Config = null;

    /**
     * Add a vhost
     *
     * @param string $vhost - host name (eq: www.something.com)
     *
     * @return string - clean vhost
     * @throws Exception
     */
    public function addVhost(string $vhost): string
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
                    'quiqqer/core',
                    'exception.vhost.exist'
                )
            );
        }

        $Config->setSection($vhost);
        $Config->save();

        $this->repair();

        return $vhost;
    }

    /**
     * @throws Exception
     */
    protected function getConfig(): ?Config
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
    public function repair(): void
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
                $Project = QUI::getProject($data['project']);
                $languages = $Project->getAttribute('langs');
            } catch (Exception $Exception) {
                QUI::getMessagesHandler()->addError($Exception->getMessage());
                continue;
            }

            foreach ($languages as $lang) {
                if (!empty($data[$lang])) {
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
     * @throws Exception
     */
    public function getList(): array
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
    public function getHostByProject(string $projectName, string $projectLang): string
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
    public function editVhost(string $vhost, array $data): void
    {
        $Config = $this->getConfig();

        if (!$Config->existValue($vhost)) {
            throw new Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.vhost.not.found'
                )
            );
        }

        $result = [];

        foreach ($data as $key => $value) {
            $key = Orthos::clear($key);

            $result[$key] = $value;
        }

        if (!isset($result["project"])) {
            throw new Exception([
                'quiqqer/core',
                'exception.vhost.missing.data.project'
            ]);
        }

        // host already exist - quiqqer/core#752
        $config = $Config->toArray();

        foreach ($config as $h => $d) {
            if ($h === $vhost) {
                continue;
            }

            if ($d['project'] !== $result['project']) {
                continue;
            }

            if ($d['lang'] !== $result['lang']) {
                continue;
            }

            throw new Exception([
                'quiqqer/core',
                'exception.vhost.same.host.already.exists'
            ]);
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
     *
     * @throws Exception
     */
    public function removeVhost(string $vhost): void
    {
        $Config = $this->getConfig();

        if (!$Config->existValue($vhost)) {
            throw new Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
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
     * @throws Exception
     */
    public function getVhost(string $vhost): bool|array
    {
        return $this->getConfig()->getSection($vhost);
    }

    /**
     * Return all hosts from the project
     *
     * @throws Exception
     */
    public function getHostsByProject(string $projectName): array
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
     * @throws Exception
     */
    public function getProjectByHost(string $vhost): bool|QUI\Projects\Project
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
    public function getRegisteredDomains(bool $includeWWW = false): array
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
            if (empty($data['project'])) {
                continue;
            }

            $domains[] = $key;

            # Parse vhosts per language
            $projectName = $data['project'];
            $Project = QUI::getProject($projectName);
            $langs = $Project->getLanguages();

            foreach ($langs as $lang) {
                if (!empty($data[$lang])) {
                    $domains[] = $data[$lang];
                }
            }

            # Parse httpshost
            if (!empty($data['httpshost'])) {
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
