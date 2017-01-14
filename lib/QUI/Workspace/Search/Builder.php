<?php

/**
 * This file contains QUI\Workspace\Search\Builder
 */
namespace QUI\Workspace\Search;

use QUI;

/**
 * Class Builder
 * Building the Search DB
 *
 * @package QUI\Workspace
 */
class Builder
{
    const TYPE_APPS = 'apps';
    const TYPE_EXTRAS = 'extras';
    const TYPE_GROUPS = 'groups';
    const TYPE_MEDIA = 'media';
    const TYPE_PROJECT = 'project';
    const TYPE_SETTINGS = 'settings';

    const TYPE_APPS_ICON = 'fa fa-diamond';
    const TYPE_EXTRAS_ICON = 'fa fa-cubes';
    const TYPE_GROUPS_ICON = 'fa fa-groups';
    const TYPE_MEDIA_ICON = 'fa fa-picture-o';
    const TYPE_PROJECT_ICON = 'fa fa-home';
    const TYPE_SETTINGS_ICON = 'fa fa-gears';

    /**
     * @var null
     */
    protected static $Instance = null;

    /**
     * @var null
     */
    protected $menu = null;

    /**
     * list of locales
     *
     * @var null
     */
    protected $locales = null;

    /**
     * @var string
     */
    protected $table = 'quiqqerBackendSearch';

    /**
     * Return the global instance
     *
     * @return Builder
     */
    public static function getInstance()
    {
        if (is_null(self::$Instance)) {
            self::$Instance = new self();
        }

        return self::$Instance;
    }

    /**
     * Return the database table name
     *
     * @return string
     */
    public function getTable()
    {
        return QUI::getDBTableName($this->table);
    }

    /**
     * Returns all available locales
     */
    public function getLocales()
    {
        if (!is_null($this->locales)) {
            return $this->locales;
        }

        $available     = QUI\Translator::getAvailableLanguages();
        $this->locales = array();

        foreach ($available as $lang) {
            $this->locales[$lang] = new QUI\Locale();
            $this->locales[$lang]->setCurrent($lang);
        }

        return $this->locales;
    }

    /**
     * Return the menu data, entries of the admin menu
     *
     * @return array
     */
    protected function getMenuData()
    {
        if (is_null($this->menu)) {
            $Menu = new QUI\Workspace\Menu();
            $menu = $Menu->createMenu();

            $this->menu = $menu;
        }

        return $this->menu;
    }

    /**
     * Build the complete search cache
     */
    public function buildCache()
    {
        try {
            $this->buildAppsCache();
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        try {
            $this->buildExtrasCache();
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        try {
            $this->buildSettingsCache();
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * Build the cache for the apps search
     */
    public function buildAppsCache()
    {
        $this->buildCacheHelper(self::TYPE_APPS);
    }

    /**
     * Build the cache for the extras search
     */
    public function buildExtrasCache()
    {
        $this->buildCacheHelper(self::TYPE_EXTRAS);
    }

    /**
     * Build the cache for the settings search
     */
    public function buildSettingsCache()
    {
        $this->buildCacheHelper(self::TYPE_SETTINGS);
    }

    /**
     * Helper to build a section / search group
     *
     * @param String $type
     */
    protected function buildCacheHelper($type)
    {
        QUI::getDataBase()->delete($this->getTable(), array(
            'searchtype' => $type
        ));

        $menu   = $this->getMenuData();
        $filter = array_filter($menu, function ($item) use ($type) {
            return $item['name'] == $type;
        });

        $data = $this->parseMenuData($filter);

        foreach ($data as $key => $entry) {
            $entry['searchtype'] = $type;

            if (empty($entry['icon'])) {
                switch ($type) {
                    case self::TYPE_APPS:
                        $entry['icon'] = self::TYPE_APPS_ICON;
                        break;

                    case self::TYPE_EXTRAS:
                        $entry['icon'] = self::TYPE_EXTRAS_ICON;
                        break;

                    case self::TYPE_GROUPS:
                        $entry['icon'] = self::TYPE_GROUPS_ICON;
                        break;

                    case self::TYPE_MEDIA:
                        $entry['icon'] = self::TYPE_MEDIA_ICON;
                        break;

                    case self::TYPE_PROJECT:
                        $entry['icon'] = self::TYPE_PROJECT_ICON;
                        break;

                    case self::TYPE_SETTINGS:
                        $entry['icon'] = self::TYPE_SETTINGS_ICON;
                        break;
                }
            }

            $this->addEntry($entry);
        }
    }

    /**
     * Add an entry
     *
     * @param array $params
     * @throws QUI\Exception
     */
    protected function addEntry($params)
    {
        $needles = array('title', 'search', 'searchtype', 'searchdata');

        foreach ($needles as $needle) {
            if (!isset($params[$needle]) || empty($params[$needle])) {
                throw new QUI\Workspace\Search\Exception(
                    'Missing params',
                    404,
                    array(
                        'params' => $params,
                        'needle' => $needle
                    )
                );
            }
        }


        if (!isset($params['description'])) {
            $params['description'] = '';
        }

        if (!isset($params['icon'])) {
            $params['description'] = '';
        }

        QUI::getDataBase()->insert($this->getTable(), $params);
    }

    /**
     * Parse menu entries to a data array
     *
     * @param array $items
     * @return array
     */
    protected function parseMenuData($items)
    {
        $data         = array();
        $searchFields = array('require', 'exec', 'onClick', 'type');

        if (!is_array($items)) {
            return array();
        }


        foreach ($items as $item) {
            $title  = $item['text'];
            $icon   = '';
            $search = $item['text'];


            // locale w. search string
            if (isset($item['locale']) && is_array($item['locale'])) {
                $search = '';
                $title  = "[{$item['locale'][0]}] {$item['locale'][1]}";

                /* @var $Locale QUI\Locale */
                foreach ($this->getLocales() as $Locale) {
                    if ($Locale->exists($item['locale'][0], $item['locale'][1])) {
                        $search .= ' ' . $Locale->get($item['locale'][0], $item['locale'][1]);
                    }
                }
            }


            if (isset($item['icon'])) {
                $icon = $item['icon'];
            }

            $searchData = array();

            foreach ($searchFields as $field) {
                if (isset($item[$field])) {
                    $searchData[$field] = $item[$field];
                }
            }


            $data[] = array(
                'title'      => $title,
                'icon'       => $icon,
                'search'     => $search,
                'searchdata' => json_encode($searchData)
            );

            if (isset($item['items'])) {
                $data = array_merge($data, $this->parseMenuData($item['items']));
            }
        }

        return $data;
    }
}
