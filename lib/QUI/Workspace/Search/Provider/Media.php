<?php

namespace QUI\Workspace\Search\Provider;

use QUI;
use QUI\Workspace\Search\ProviderInterface;

class Media implements ProviderInterface
{
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
        $filter = array_flip($params['filterGroups']);

        $projects = QUI::getProjectManager()->getProjectList();
        $results  = array();

        // if no groups are selected, return empty result list
        if (!isset($filter['file'])
            && !isset($filter['image'])
            && !isset($filter['folder'])
        ) {
            return $results;
        }

        $where = array(
            '(`title` LIKE :search OR `mime_type` LIKE :search)'
        );

        $whereOr = array();

        if (isset($filter['file'])) {
            $whereOr[] = '`type` = \'file\'';
        }

        if (isset($filter['image'])) {
            $whereOr[] = '`type` = \'image\'';
        }

        if (isset($filter['folder'])) {
            $whereOr[] = '`type` = \'folder\'';
        }

        $where[] = '(' . implode(' OR ', $whereOr) . ')';

        $PDO = QUI::getDataBase()->getPDO();

        /* @var $Project QUI\Projects\Project */
        foreach ($projects as $Project) {
            $Media = $Project->getMedia();

            $sql = "SELECT id,title,file,type FROM " . $Media->getTable();
            $sql .= " WHERE " . implode(' AND ', $where);

            if (isset($params['limit'])) {
                $sql .= " LIMIT " . (int)$params['limit'];
            }

            $Stmt = $PDO->prepare($sql);

            // bind
            $Stmt->bindValue(':search', '%' . $search . '%', \PDO::PARAM_STR);

            try {
                $Stmt->execute();
                $result = $Stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\Exception $Exception) {
                QUI\System\Log::addError(
                    self::class . ' :: search -> ' . $Exception->getMessage()
                );

                continue;
            }

            $projectName = $Project->getName();

            foreach ($result as $row) {
                $groupLabel = QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'search.provider.media.group.label',
                    array(
                        'projectName' => $projectName
                    )
                );

                switch ($row['type']) {
                    case 'file':
                        $icon = 'fa fa-file-text-o';
                        break;

                    case 'folder':
                        $icon = 'fa fa-folder-o';
                        break;

                    default:
                        $icon = 'fa fa-picture-o';
                }

                $results[] = array(
                    'id'          => $projectName . '-' . $row['id'],
                    'title'       => $row['title'],
                    'description' => $row['file'],
                    'icon'        => $icon,
                    'groupLabel'  => $groupLabel,
                    'group'       => $projectName . '-media'
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
                'require' => 'package/quiqqer/quiqqer/bin/QUI/controls/workspace/search/provider/Media',
                'params'  => array(
                    'project' => $data[0],
                    'id'      => $data[1]
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
                'group' => 'folder',
                'label' => array(
                    'quiqqer/quiqqer',
                    'search.provider.media.filter.folder.label'
                )
            ),
            array(
                'group' => 'image',
                'label' => array(
                    'quiqqer/quiqqer',
                    'search.provider.media.filter.image.label'
                )
            ),
            array(
                'group' => 'file',
                'label' => array(
                    'quiqqer/quiqqer',
                    'search.provider.media.filter.file.label'
                )
            )
        );
    }
}
