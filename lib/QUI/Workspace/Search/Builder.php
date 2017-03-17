<?php

/**
 * This file contains QUI\Workspace\Search\Builder
 */
namespace QUI\Workspace\Search;

use Composer\Cache;
use QUI;
use QUI\Cache\Manager as CacheManager;
use QUI\Permissions\Permission;
use QUI\Utils\XML\Settings as SettingsXML;
use QUI\Utils\Text\XML;
use QUI\Utils\DOM as DOMUtils;

/**
 * Class Builder
 * Building the Search DB
 *
 * @package QUI\Workspace
 */
class Builder
{
    const TYPE_APPS    = 'apps';
    const TYPE_EXTRAS  = 'extras';
    const TYPE_PROJECT = 'project';
    const TYPE_PROFILE = 'profile';

    const FILTER_NAVIGATION = 'navigation';
    const FILTER_SETTINGS   = 'settings';

    const TYPE_APPS_ICON    = 'fa fa-diamond';
    const TYPE_EXTRAS_ICON  = 'fa fa-cubes';
    const TYPE_PROJECT_ICON = 'fa fa-home';
    const TYPE_PROFILE_ICON = 'fa fa-id-card-o';

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
     * Return the complete available list of all providers classes
     *
     * @return array
     */
    protected function getProviderClasses()
    {
        $cache = 'workspace/search/providers';

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Cache\Exception $Exception) {
        }

        $packages = QUI::getPackageManager()->getInstalled();
        $provider = array();

        foreach ($packages as $package) {
            try {
                $Package = QUI::getPackage($package['name']);

                if (!$Package->isQuiqqerPackage()) {
                    continue;
                }

                $packageProvider = $Package->getProvider();

                if (isset($packageProvider['desktopSearch'])) {
                    $provider = array_merge($provider, $packageProvider['desktopSearch']);
                }
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        QUI\Cache\Manager::set($cache, $provider);

        return $provider;
    }

    /**
     * Get all groups the search results can be grouped by
     *
     * @return array
     */
    public function getFilterGroups()
    {
        $cacheName = 'quiqqer/desktopsearch/filtergroups';

        try {
            return json_decode(CacheManager::get($cacheName), true);
        } catch (\Exception $Exception) {
            // nothing, retrieve filter groups freshly
        }

        $providers = $this->getProvider();
        $groups    = array(
            array(
                'group' => self::FILTER_NAVIGATION,
                'label' => array(
                    'quiqqer/quiqqer',
                    'search.builder.filter.label.' . self::FILTER_NAVIGATION
                )
            )
        );

        /** @var ProviderInterface $Provider */
        foreach ($providers as $Provider) {
            $providerGroups = $Provider->getFilterGroups();

            foreach ($providerGroups as $group) {
                if (!isset($groups[$group['group']])) {
                    $groups[$group['group']] = $group;
                }
            }
        }

        $groups = array_values($groups);

        CacheManager::set($cacheName, json_encode($groups));

        return $groups;
    }

    /**
     * Return the available provider instances
     *
     * @param string|bool $provider - optional, Return a specific provider
     * @return array|ProviderInterface
     *
     * @throws QUI\Workspace\Search\Exception
     */
    public function getProvider($provider = false)
    {
        $result = array();

        foreach ($this->getProviderClasses() as $cls) {
            if (!class_exists($cls)) {
                continue;
            }

            try {
                $Instance = new $cls();

                if ($Instance instanceof ProviderInterface) {
                    $result[] = $Instance;
                }

                if ($provider && get_class($Instance) == $provider) {
                    return $Instance;
                }
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException(
                    $Exception,
                    QUI\System\Log::LEVEL_ERROR,
                    array(
                        'method' => 'QUI\Workspace\Search::getProviderInstances'
                    )
                );
            }
        }

        if ($provider) {
            throw new QUI\Workspace\Search\Exception('Provider not found', 404);
        }

        return $result;
    }

    /**
     * Return the menu data, entries of the admin menu
     *
     * @return array
     */
    public function getMenuData()
    {
        if (is_null($this->menu)) {
            $Menu = new QUI\Workspace\Menu();
            $menu = $Menu->createMenu();

            $this->menu = $menu;
        }

        return $this->menu;
    }

    /**
     * Executed at the QUIQQER Setup
     */
    public function setup()
    {
        QUI\Cache\Manager::clear('workspace/search/providers');

        $this->buildCache();
    }

    /**
     * Build the complete search cache and clears the cache
     */
    public function buildCache()
    {
        QUI::getDataBase()->table()->truncate($this->getTable());

        // apps
        try {
            $this->buildAppsCache();
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        // extras
        try {
            $this->buildExtrasCache();
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        // profile
        try {
            $this->buildProfileCache();
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        $provider = $this->getProvider();

        /* @var $Provider ProviderInterface */
        foreach ($provider as $Provider) {
            $Provider->buildCache();
        }
    }

    /**
     * Build the cache for the apps search
     */
    public function buildAppsCache()
    {
        $this->buildMenuCacheHelper(self::TYPE_APPS);
    }

    /**
     * Build the cache for the extras search
     */
    public function buildExtrasCache()
    {
        $this->buildMenuCacheHelper(self::TYPE_EXTRAS);
    }

    /**
     * Build the cache for the profile search
     */
    public function buildProfileCache()
    {
        QUI::getDataBase()->delete($this->getTable(), array(
            'group' => self::TYPE_PROFILE
        ));

        $locales          = $this->getLocales();
        $QUILocale        = QUI::getLocale();
        $quiLocaleCurrent = $QUILocale->getCurrent();

        /** @var QUI\Locale $Locale */
        foreach ($locales as $Locale) {
            // temporarily set language of $QUILocale to current of $Locale (for template parsing)
            $QUILocale->setCurrent($Locale->getCurrent());

            $menu = $this->getMenuData();

            $filter = array_filter($menu, function ($item) {
                return $item['name'] == self::TYPE_PROFILE;
            });

            $groupLabel = $Locale->get('quiqqer/system', 'profile');
            $data       = $this->parseMenuData($filter, $Locale);

            foreach ($data as $key => $entry) {
                // add special search terms to user profile entry
                if ($entry['name'] == 'userProfile') {
                    $entry['search'] .= ' ' . implode(' ', $this->getProfileSearchterms());
                }

                $entry['group']       = self::TYPE_PROFILE;
                $entry['groupLabel']  = $groupLabel;
                $entry['filterGroup'] = self::FILTER_NAVIGATION;

                if (!isset($entry['icon'])) {
                    $entry['icon'] = self::TYPE_PROFILE_ICON;
                }

                $searchData = json_decode($entry['searchdata'], true);

                if (empty($searchData['require'])) {
                    continue;
                }

                $this->addEntry($entry, $Locale->getCurrent());
            }
        }

        // reset $QUILocale
        $QUILocale->setCurrent($quiLocaleCurrent);
    }

    /**
     * Gets the WHERE constraint based on user permissions
     *
     * @param array $filters - the filters that are considered
     * @return array
     */
    public function getWhereConstraint($filters)
    {
        $where = array(
            'navApps'   => '`group` != \'' . self::TYPE_APPS . '\'',
            'navExtras' => '`group` != \'' . self::TYPE_EXTRAS . '\'',
        );

        foreach ($filters as $filter) {
            switch ($filter) {
                case self::FILTER_NAVIGATION:
                    if (Permission::hasPermission('quiqqer.menu.apps')) {
                        unset($where['navApps']);
                    }

                    if (Permission::hasPermission('quiqqer.menu.extras')) {
                        unset($where['navExtras']);
                    }
                    break;
            }
        }

        return array_values($where);
    }

    /**
     * Helper to build a section / search group via menu items
     *
     * @param string $type
     */
    protected function buildMenuCacheHelper($type)
    {
        QUI::getDataBase()->delete($this->getTable(), array(
            'group' => $type
        ));

        $menu = $this->getMenuData();

        $filter = array_filter($menu, function ($item) use ($type) {
            return $item['name'] == $type;
        });

        $locales = $this->getLocales();

        /** @var QUI\Locale $Locale */
        foreach ($locales as $Locale) {
            $typeLabel = '';

            switch ($type) {
                case self::TYPE_APPS:
                    $typeLabel = $Locale->get(
                        'quiqqer/system',
                        'menu.apps.text'
                    );
                    break;

                case self::TYPE_EXTRAS:
                    $typeLabel = $Locale->get(
                        'quiqqer/system',
                        'menu.goto.text'
                    );
                    break;

                case self::TYPE_PROFILE:
                    $typeLabel = $Locale->get(
                        'quiqqer/system',
                        'profile'
                    );
                    break;
            }

            $groupLabel = $Locale->get(
                'quiqqer/quiqqer',
                'search.builder.group.menu.label',
                array(
                    'type' => $typeLabel
                )
            );

            $data = $this->parseMenuData($filter, $Locale);

            foreach ($data as $key => $entry) {
                $entry['group']       = $type;
                $entry['groupLabel']  = $groupLabel;
                $entry['filterGroup'] = self::FILTER_NAVIGATION;

                switch ($type) {
                    case self::TYPE_APPS:
                        if (empty($entry['icon'])) {
                            $entry['icon'] = self::TYPE_APPS_ICON;
                        }
                        break;

                    case self::TYPE_EXTRAS:
                        if (empty($entry['icon'])) {
                            $entry['icon'] = self::TYPE_EXTRAS_ICON;
                        }
                        break;

                    case self::TYPE_PROJECT:
                        if (empty($entry['icon'])) {
                            $entry['icon'] = self::TYPE_PROJECT_ICON;
                        }
                        break;

                    case self::TYPE_PROFILE:
                        if (empty($entry['icon'])) {
                            $entry['icon'] = self::TYPE_PROFILE_ICON;
                        }
                        break;
                }

                $searchData = json_decode($entry['searchdata'], true);

                if (empty($searchData['require'])) {
                    continue;
                }

                $this->addEntry($entry, $Locale->getCurrent());
            }
        }
    }

    /**
     * Add cache entry for a specific language
     *
     * @param array $params
     * @param string $lang
     * @throws QUI\Exception
     */
    public function addEntry($params, $lang)
    {
        $needles = array('title', 'search', 'group', 'filterGroup', 'searchdata');

        foreach ($needles as $needle) {
            if (!isset($params[$needle]) || empty($params[$needle])) {
                throw new QUI\Workspace\Search\Exception(
                    'Missing params',   #locale
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

        if (isset($params['name'])) {
            unset($params['name']);
        }

        if (isset($params['groupLabel'])
            && is_array($params['groupLabel'])
        ) {
            $params['groupLabel'] = json_encode($params['groupLabel']);
        }

        $params['lang'] = $lang;

        QUI::getDataBase()->insert($this->getTable(), $params);
    }

    /**
     * Parse menu entries to a data array
     *
     * @param array $items
     * @param QUI\Locale $Locale
     * @param string $parentTitle (optional) - title of parent menu node
     * @return array
     */
    protected function parseMenuData($items, $Locale, $parentTitle = null)
    {
        $data         = array();
        $searchFields = array('require', 'exec', 'onClick', 'type');

        if (!is_array($items)) {
            return array();
        }

        foreach ($items as $item) {
            $title  = $item['text'];
            $search = $item['text'];

            // locale w. search string
            if (isset($item['locale']) && is_array($item['locale'])) {
                $search = $Locale->get($item['locale'][0], $item['locale'][1]);
                $title  = $search;
            }

            $description = $title;

            if (!is_null($parentTitle)) {
                $description = $parentTitle . ' -> ' . $description;    // @todo Trennzeichen ggf. ändern
            }

            $icon = '';

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
                'name'        => $item['name'],
                'title'       => $title,
                'description' => $description,
                'icon'        => $icon,
                'search'      => $search,
                'searchdata'  => json_encode($searchData)
            );

            if (isset($item['items'])
                && !empty($item['items'])
            ) {
                $data = array_merge($data, $this->parseMenuData($item['items'], $Locale, $description));
            }
        }

        return $data;
    }

    /**
     * Get search terms from user profile (dynamic) template
     *
     * @return array - list of search terms
     */
    protected function getProfileSearchterms()
    {
        $search = array();
        $html   = QUI::getUsers()->getProfileTemplate();

        $Doc = new \DOMDocument();
        $Doc->loadHTML($html);

        $Path = new \DOMXPath($Doc);

        // table headers
        $titles = $Path->query('//table/thead/tr/th');

        foreach ($titles as $Title) {
            $search[] = utf8_decode(trim(DOMUtils::getTextFromNode($Title)));
        }

        // labels
        $labels = $Path->query('//label/span');

        /** @var \DOMNode $Label */
        foreach ($labels as $Label) {
            $search[] = utf8_decode(trim(DOMUtils::getTextFromNode($Label)));
        }

        return $search;
    }
}
