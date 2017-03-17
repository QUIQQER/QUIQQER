<?php

namespace QUI\Workspace\Search\Provider;

use QUI;
use QUI\Workspace\Search\ProviderInterface;

class Sites implements ProviderInterface
{
    const FILTER_SITES = 'sites';

    /**
     * Build the cache
     *
     * @return mixed
     */
    public function buildCache()
    {

    }

    /**
     * Execute a search
     *
     * @param string $search
     * @param array $params
     * @return mixed
     */
    public function search($search, $params = array())
    {
        if (!in_array('sites', $params['filterGroups'])) {
            return array();
        }

        $projects = QUI::getProjectManager()->getProjectList();
        $results  = array();

        /** @var QUI\Projects\Project $Project */
        foreach ($projects as $Project) {
            $siteIds = $Project->getSitesIds(array(
                'where'    => array(
                    'active' => -1
                ),
                'where_or' => array(
                    'title' => array(
                        'type'  => '%LIKE%',
                        'value' => $search
                    ),
                    'name'  => array(
                        'type'  => '%LIKE%',
                        'value' => $search
                    )
                ),
                'limit' => isset($params['limit']) ? (int)$params['limit'] : null
            ));

            $projectName = $Project->getName();
            $projectLang = $Project->getLang();
            $groupLabel  = QUI::getLocale()->get(
                'quiqqer/quiqqer',
                'search.provider.sites.group.label',
                array(
                    'projectName' => $projectName,
                    'projectLang' => $projectLang
                )
            );

            $group = 'project-' . $projectName . '-' . $projectLang;

            foreach ($siteIds as $row) {
                $siteId = $row['id'];
                $Site   = $Project->get($siteId);

                $results[] = array(
                    'id'          => $projectName . '-' . $projectLang . '-' . $siteId,
                    'title'       => $Site->getAttribute('title'),
                    'description' => $Site->getUrlRewritten(),
                    'icon'        => 'fa fa-file-o',
                    'groupLabel'  => $groupLabel,
                    'group'       => $group
                );
            }
        }

        return $results;
    }

    /**
     * Return a search entry
     *
     * @param integer $id
     * @return mixed
     */
    public function getEntry($id)
    {
        $data = explode('-', $id);

        return array(
            'searchdata' => json_encode(array(
                'require' => 'package/quiqqer/quiqqer/bin/QUI/controls/workspace/search/provider/Sites',
                'params'  => array(
                    'projectName' => $data[0],
                    'projectLang' => $data[1],
                    'siteId'      => $data[2]
                )
            ))
        );
    }

    /**
     * Get all available search groups of this provider.
     * Search results can be filtered by these search groups.
     *
     * @return array
     */
    public function getFilterGroups()
    {
        return array(
            array(
                'group' => self::FILTER_SITES,
                'label' => array(
                    'quiqqer/quiqqer',
                    'search.provider.sites.filter.sites.label'
                )
            )
        );
    }
}
