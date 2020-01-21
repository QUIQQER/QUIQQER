<?php

use QUI\Utils\Grid;
use QUI\Utils\Security\Orthos;

/**
 * Return the children of a media folder
 *
 * @param string $project - Name of the project
 * @param string|integer $folderid - Folder-ID
 * @param string $params - JSON Order Params
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_media_folder_children',
    function ($project, $folderid, $params) {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media   = $Project->getMedia();
        $File    = $Media->get($folderid);

        /* @var $File \QUI\Projects\Media\Folder */
        $Grid   = new Grid($params);
        $params = Orthos::clearArray(\json_decode($params, true));

        $children        = [];
        $showHiddenFiles = !empty($params['showHiddenFiles']) && $params['showHiddenFiles'];
        $params          = $Grid->parseDBParams($params);

        if ($showHiddenFiles === false) {
            $params['where']['hidden'] = 0;
        }

        $_children = $File->getChildrenIds($params);

        $getUserName = function ($uid) {
            try {
                return QUI::getUsers()->get($uid)->getName();
            } catch (QUI\Exception $Exception) {
            }

            return '---';
        };

        // create children data
        foreach ($_children as $id) {
            try {
                $Child = $Media->get($id);
                $data  = QUI\Projects\Media\Utils::parseForMediaCenter($Child);

                $data['c_user'] = $getUserName($data['c_user']);
                $data['e_user'] = $getUserName($data['e_user']);

                $children[] = $data;
            } catch (QUI\Exception $Exception) {
                $child = [
                    'id'    => $id,
                    'name'  => $Exception->getAttribute('name'),
                    'title' => $Exception->getAttribute('title'),
                    'error' => true
                ];

                $children[] = $child;
            }
        }

        // Set count parameter to get total count of results
        $params['count'] = true;

        return $Grid->parseResult(
            $children,
            $File->getChildrenIds($params)
        );
    },
    ['project', 'folderid', 'params'],
    'Permission::checkAdminUser'
);
