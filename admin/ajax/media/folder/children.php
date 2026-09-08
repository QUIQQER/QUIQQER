<?php

/**
 * Return the children of a media folder
 *
 * @param string $project - Name of the project
 * @param string|integer $folderid - Folder-ID
 * @param string $params - JSON Order Params
 *
 * @return array
 */

use QUI\Projects\Media\Folder;
use QUI\Utils\Grid;
use QUI\Utils\Security\Orthos;

QUI::getAjax()->registerFunction(
    'ajax_media_folder_children',
    static function ($project, $folderid, $params): array {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();
        $File = $Media->get($folderid);

        if (!($File instanceof Folder)) {
            return [];
        }

        $File->checkPermission('quiqqer.projects.media.view');

        if (is_string($params)) {
            $params = json_decode($params, true);
        }

        $params = Orthos::clearArray($params);
        $Grid = new Grid($params);

        $children = [];
        $showHiddenFiles = !empty($params['showHiddenFiles']);
        $params = $Grid->parseDBParams($params);

        if ($showHiddenFiles === false) {
            $params['where']['hidden'] = 0;
        }

        $_children = $File->getChildrenIds($params);

        if (!is_array($_children)) {
            $_children = [];
        }

        $getUserName = static function ($uid): string {
            try {
                return QUI::getUsers()->get($uid)->getName();
            } catch (QUI\Exception) {
            }

            return '---';
        };

        // create children data
        foreach ($_children as $id) {
            try {
                $Child = $Media->get($id);
                $data = QUI\Projects\Media\Utils::parseForMediaCenter($Child);

                $data['c_user'] = $getUserName($data['c_user']);
                $data['e_user'] = $getUserName($data['e_user']);

                $children[] = $data;
            } catch (QUI\Exception $Exception) {
                $child = [
                    'id' => $id,
                    'name' => $Exception->getAttribute('name'),
                    'title' => $Exception->getAttribute('title'),
                    'extension' => '',
                    'error' => true
                ];

                $children[] = $child;
            } catch (Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        // Set count parameter to get total count of results
        $params['count'] = true;
        $count = $File->getChildrenIds($params);

        if (is_array($count)) {
            $count = count($count);
        }

        return $Grid->parseResult(
            $children,
            $count
        );
    },
    ['project', 'folderid', 'params'],
    'Permission::checkAdminUser'
);
