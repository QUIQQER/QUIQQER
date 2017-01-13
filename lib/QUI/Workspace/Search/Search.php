<?php

/**
 * This file contains QUI\Workspace\Search\Search
 */
namespace QUI\Workspace\Search;

use QUI;

/**
 * Class Search
 *
 * @package QUI\Workspace
 */
class Search
{
    /**
     * @var null
     */
    protected static $Instance = null;

    /**
     * @return Search
     */
    public static function getInstance()
    {
        if (is_null(self::$Instance)) {
            self::$Instance = new self();
        }

        return self::$Instance;
    }

    /**
     * Execute the search
     *
     * @param string $string - search string
     * @param array $params - search query params
     *
     * @return array
     */
    public function search($string, $params = array())
    {
        $query = array(
            'from'  => Builder::getInstance()->getTable(),
            'where' => array(
                'search' => array(
                    'type'  => '%LIKE%',
                    'value' => $string
                )
            ),
            'limit' => 20
        );

        if (isset($params['limit'])) {
            $query['limit'] = $params['limit'];
        }

        $Locale = QUI::getLocale();
        $result = QUI::getDataBase()->fetch($query);

        foreach ($result as $key => $data) {
            $result[$key]['title']       = $Locale->parseLocaleString($data['title']);
            $result[$key]['description'] = $Locale->parseLocaleString($data['description']);
        }

        return $result;
    }
}
