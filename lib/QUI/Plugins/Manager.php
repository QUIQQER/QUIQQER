<?php

/**
 * This file contains \QUI\Plugins\Manager
 */

namespace QUI\Plugins;

use QUI;

/**
 * Plugin Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @todo Plugin Methoden Benennung sollte bisschen überdacht werden
 * @todo Muss neu geschrieben werden -> als Paket (Package) und PaketManager (PackageManager)
 *
 * @errorcodes 8XX = Project Errors
 * @deprecated
 */
class Manager extends QUI\QDOM
{
    /**
     * loaded plugins
     * @var array
     */
    protected $plugins = array();

    /**
     * plugin config
     * @var \QUI\Config
     */
    protected $Config = null;

    /**
     * Loaded flag
     * @var boolean
     */
    protected $loaded = false;

    /**
     * loaded group extentions
     * @var array|boolean
     * @deprecated
     */
    protected $groupplugins = false;

    /**
     * Konstruktor
     * Ließt Plugin Config ein
     */
    public function __construct()
    {
        $this->Config = QUI::getConfig('etc/plugins.ini');
    }

    /**
     * Führt das Setup aller aktiven Plugins auf das Projekt aus
     */
    public function setup()
    {
        $plugins = $this->get();

        foreach ($plugins as $Plugin) {
            /* @var $Plugin QUI\Plugins\Plugin */
            $Plugin->install();
        }
    }

    /**
     * Aktiviert ein Plugin und führt die Installation durch
     *
     * @param Plugin $Plugin
     */
    public function activate(Plugin $Plugin)
    {
        $Plugin->install();

        $Config = $this->Config;
        $Config->set($Plugin->getAttribute('name'), null, 1);
        $Config->save();

        $this->clearCache();
    }

    /**
     * Deaktiviert ein Plugin
     *
     * Löscht die Tabellen nicht,
     * dies wird erst bei einem Löschen des Plugins gemacht (uninstall)
     *
     * @param Plugin $Plugin
     */
    public function deactivate(Plugin $Plugin)
    {
        $Config = $this->Config;
        /* @var $Config QUI\Config */

        $Config->del($Plugin->getAttribute('name'));
        $Config->save();

        $this->clearCache();
    }

    /**
     * Gibt alle Plugins zurück die verfügbar sind
     *
     * @param boolean $order - Sortiert bekommen (optional)
     * @param boolean $onlynames - Nur Namen, keine Objekte (optional)
     *
     * @return array
     * @deprecated use package manager
     */
    public function getAvailablePlugins($order = false, $onlynames = false)
    {
        $list   = QUI\Utils\System\File::readDir(OPT_DIR);
        $result = array();

        foreach ($list as $dir) {
            if (!is_dir(OPT_DIR . $dir) || strpos($dir, '.') === 0) {
                continue;
            }

            if ($onlynames) {
                $result[] = $dir;
                continue;
            }

            $result[] = $this->get($dir);
        }

        if ($order) {
            if ($onlynames) {
                sort($result);
            } else {
                $_result = array();

                foreach ($result as $Plugin) {
                    /* @var $Plugin QUI\Plugins\Plugin */
                    $c                   = $Plugin->getAttribute('config');
                    $_result[$c['name']] = $Plugin;
                }

                ksort($_result);
                $result = array();

                foreach ($_result as $Plugin) {
                    $result[] = $Plugin;
                }
            }
        }

        return $result;
    }

    /**
     * Return all inactive Plugins
     *
     * @param boolean $order - get the list ordered
     * @return array
     */
    public function getInactivePlugins($order = false)
    {
        $Config = $this->Config;
        /* @var $Config \QUI\Config */
        $list = $this->getAvailablePlugins($order);

        $result = array();

        foreach ($list as $Plugin) {
            /* @var $Plugin QUI\Plugins\Plugin */
            if ($Config->getSection($Plugin->getAttribute('name')) === false) {
                $result[] = $Plugin;
            }
        }

        return $result;
    }

    /**
     * Gibt alle Seitentypen zurück die verfügbar sind
     *
     * @param \QUI\Projects\Project|boolean $Project - optional
     * @return array
     */
    public function getAvailableTypes($Project = false)
    {
        $types     = array();
        $installed = QUI::getPackageManager()->getInstalled();

        foreach ($installed as $package) {
            $name    = $package['name'];
            $siteXml = OPT_DIR . $name . '/site.xml';

            if (!file_exists($siteXml)) {
                continue;
            }

            $typeList = QUI\Utils\XML::getTypesFromXml($siteXml);

            foreach ($typeList as $Type) {
                /* @var $Type \DOMElement */
                $types[$name][] = array(
                    'type' => $name . ':' . $Type->getAttribute('type'),
                    'icon' => $Type->getAttribute('icon'),
                    'text' => $this->getTypeName(
                        $name . ':' . $Type->getAttribute('type')
                    )
                );
            }
        }

        ksort($types);

        // standard to top
        $types = array_reverse($types, true);

        $types['standard'] = array(
            'type' => 'standard',
            'icon' => 'fa fa-file-o'
        );

        $types = array_reverse($types, true);

        return $types;
    }

    /**
     * Löscht den Plugin Cache plus den Projekt Cache
     */
    public static function clearCache()
    {
        QUI\Cache\Manager::clearAll();
    }

    /**
     * Erzeugt ein Cachefile vom Plugin
     *
     * @param string $class
     * @param QUI\Plugins\Plugin $Plugin
     * @return boolean
     */
    protected function createCache($class, $Plugin)
    {
        // Kein Cache für Standard Plugins
        if ($class == 'QUI\\Plugins\\Plugin') {
            return false;
        }

        QUI\Cache\Manager::set('plugin-' . $class, $Plugin->getAttributes());
        return true;
    }

    /**
     * Gibt das Plugin zurück wenn ein Cachefile existiert
     *
     * @param string $class
     * @return boolean|object
     *
     * @throws QUI\Exception
     *
     * @todo mal überdenken
     */
    protected function getCache($class)
    {
        try {
            $attributes = QUI\Cache\Manager::get('plugin-' . $class);

            if (empty($attributes)) {
                return false;
            }
        } catch (QUI\Cache\Exception $Exception) {
            return false;
        }

        if (isset($attributes['_file_']) &&
            !class_exists($class) &&
            file_exists($attributes['_file_'])
        ) {
            require_once $attributes['_file_'];
        }

        if (!class_exists($class)) {
            throw new QUI\Exception('Konnte Plugin ' . $class . ' nicht laden');
        }

        /* @var $Plugin QUI\Plugins\Plugin */
        $Plugin = new $class();
        $Plugin->setAttributes($attributes);

        return $Plugin;
    }

    /**
     * Plugin bekommen
     *
     * @param string $name - Name des Plugins
     * @param string $type - Seitentype
     *
     * @return Plugin
     * @todo Unbedingt ändern, get gibt nur aktiv Plugins zurück -> überarbeiten
     * @todo überdenken -> wird das noch gebraucht?
     */
    public function get($name = false, $type = false)
    {
        if ($name === false) {
            return $this->getAll();
        }

        $class = 'Plugin_' . $name;

        // Falls das Plugin schon mal gehohlt wurde, dann gleich zurück geben
        if (isset($this->plugins[$class])) {
            return $this->plugins[$class];
        }

        // Falls Plugin schon im Cache steckt
        $Plugin = $this->getCache($class);

        if ($Plugin) {
            $this->plugins[$class] = $Plugin;
            return $Plugin;
        }

        $dir = str_replace('Plugin_', '', $name);
        $dir = explode('_', $dir);

        $last = end($dir);
        $dir  = implode($dir, '/');

        // Pluginfile laden falls noch nicht getan
        $f_plg = OPT_DIR . $dir . '/' . ucfirst($last) . '.php';

        if (!class_exists($class) && file_exists($f_plg)) {
            require_once $f_plg;
        }

        if (!class_exists($class) &&
            file_exists(OPT_DIR . $name . '/' . ucfirst($name) . '.php')
        ) {
            $f_plg = OPT_DIR . $name . '/' . ucfirst($name) . '.php';
            require_once $f_plg;
        }

        if (!class_exists($class)) {
            $class = '\\QUI\\Plugins\\Plugin';
        }

        /* @var $Plugin QUI\Plugins\Plugin */
        $Plugin = new $class();
        $Plugin->setAttribute('name', $name);
        $Plugin->setAttribute('_file_', $f_plg);
        $Plugin->setAttribute('_folder_', OPT_DIR . $dir . '/');

        $config = $this->Config->toArray();

        if (isset($config[$name]) && $config[$name] == 1) {
            $Plugin->setAttribute('active', 1);
        }

        // $Plugin->load();

        $this->plugins[$class] = $Plugin;

        // Cache fürs Plugin erzeugen
        $this->createCache($class, $Plugin);

        return $Plugin;
    }

    /**
     * Befindet sich das Plugin im System
     *
     * @param string $plugin
     * @return boolean
     * @throws \QUI\Exception
     */
    public function existPlugin($plugin)
    {
        if (is_dir(OPT_DIR . $plugin)) {
            return true;
        }

        throw new QUI\Exception('Plugin nicht gefunden', 404);
    }

    /**
     * Gibt dir das Plugin zurück wenn es verfügbar ist
     *
     * @param string $plugin
     * @return Plugin
     *
     * @throws \QUI\Exception
     */
    public function getPlugin($plugin)
    {
        $Plugin = $this->get($plugin);

        if ($Plugin->getAttribute('active')) {
            return $Plugin;
        }

        throw new QUI\Exception('Plugin nicht verfügbar', 403);
    }

    /**
     * Ist das Plugin aktiv?
     *
     * @param string $plugin
     * @return boolean
     */
    public function isAvailable($plugin)
    {
        $config = $this->Config->toArray();

        if (isset($config[$plugin]) && $config[$plugin] == 1) {
            return true;
        }

        return false;
    }

    /**
     * Gibt das zuständige Plugin über den Seitetyp zurück
     *
     * @param string $type
     * @return Plugin
     */
    public function getPluginByType($type)
    {
        return $this->get(
            str_replace('/', '_', $type),
            $type
        );
    }

    /**
     * Get the full Type name
     *
     * @param string $type - site type
     * @return string
     */
    public function getTypeName($type)
    {
        if ($type == 'standard' || empty($type)) {
            return 'Standard';
        }

        // \QUI\System\Log::write( $type );
        $data = $this->getSiteXMLDataByType($type);

        if (isset($data['locale'])) {
            return \QUI::getLocale()->get(
                $data['locale']['group'],
                $data['locale']['var']
            );
        }

        if (!isset($data['value']) || empty($data['value'])) {
            return $type;
        }

        $value = explode(' ', $data['value']);

        if (QUI::getLocale()->exists($value[0], $value[1])) {
            return QUI::getLocale()->get($value[0], $value[1]);
        }

        return $type;
    }

    /**
     * Return the type icon
     *
     * @param string $type
     * @return string
     */
    public function getIconByType($type)
    {
        $data = $this->getSiteXMLDataByType($type);

        if (isset($data['icon'])) {
            return $data['icon'];
        }

        return '';
    }

    /**
     * Return the data for a type from its site.xml
     * https://dev.quiqqer.com/quiqqer/quiqqer/wikis/Site-Xml
     *
     * @param string $type
     * @return boolean|array
     */
    protected function getSiteXMLDataByType($type)
    {
        $cache = 'pluginManager/data/' . $type;

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Cache\Exception $Exception) {
        }

        if (strpos($type, ':') === false) {
            return false;
        }

        $explode = explode(':', $type);
        $package = $explode[0];
        $type    = $explode[1];

        $siteXml = OPT_DIR . $package . '/site.xml';

        if (!file_exists($siteXml)) {
            return false;
        }

        $Dom   = QUI\Utils\XML::getDomFromXml($siteXml);
        $XPath = new \DOMXPath($Dom);
        $Types = $XPath->query('//type[@type="' . $type . '"]');

        if (!$Types->length) {
            return false;
        }

        /* @var $Type \DOMElement */
        $Type = $Types->item(0);
        $data = array();

        if ($Type->getAttribute('icon')) {
            $data['icon'] = $Type->getAttribute('icon');
        }

        if ($Type->getAttribute('extend')) {
            $data['extend'] = $Type->getAttribute('extend');
        }

        $loc = $Type->getElementsByTagName('locale');

        if ($loc->length) {
            $data['locale'] = array(
                'group' => $loc->item(0)->getAttribute('group'),
                'var'   => $loc->item(0)->getAttribute('var')
            );
        }

        $data['value'] = trim($Type->nodeValue);

        QUI\Cache\Manager::set($cache, $data);

        return $data;
    }

    /**
     * Gibt alle Plugins zurück
     *
     * @return array
     */
    public function getAll()
    {
        if ($this->loaded) {
            return $this->plugins;
        }

        $config = $this->Config->toArray();

        foreach ($config as $key => $value) {
            $this->get($key);
        }

        $this->loaded = true;
        return $this->plugins;
    }

    /**
     * Gibt die Plugin Gruppen Erweiterungen zurück
     *
     * @return array
     * @deprecated
     */
    public function getListOfGroupPlugins()
    {
        return array();
    }
}
