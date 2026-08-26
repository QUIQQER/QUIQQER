<?php

/**
 * Return the data of the fileid
 *
 * @param string $project - Project name
 * @param string $fileid - JSON String|Array
 *
 * @return array
 */

use QUI\Projects\Media\Utils;

QUI::getAjax()->registerFunction(
    'ajax_media_details',
    static function ($project, $fileid) {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();

        $normalizeMediaId = static function ($value): int|string|null {
            if (is_string($value)) {
                $value = trim($value);

                if ($value === '' || $value === 'null') {
                    return null;
                }
            }

            if (Utils::isMediaUrl($value)) {
                return $value;
            }

            if (is_numeric($value)) {
                return (int)$value;
            }

            return null;
        };

        if (is_string($fileid)) {
            $decoded = json_decode($fileid, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $fileid = $decoded;
            }
        }

        if (!is_array($fileid)) {
            $fileid = $normalizeMediaId($fileid);

            if ($fileid === null) {
                throw new QUI\Exception('Missing or invalid media id');
            }

            if (Utils::isMediaUrl($fileid)) {
                $File = Utils::getMediaItemByUrl($fileid);
            } else {
                $File = $Media->get((int)$fileid);
            }

            $attr = $File->getAttributes();

            try {
                $attr['c_username'] = QUI::getUsers()->get((string)$attr['c_user'])->getName();
            } catch (QUI\Exception) {
                $attr['c_username'] = '---';
            }

            try {
                $attr['e_username'] = QUI::getUsers()->get((string)$attr['e_user'])->getName();
            } catch (QUI\Exception) {
                $attr['e_username'] = '---';
            }

            if (!Utils::isImage($File)) {
                return $attr;
            }

            if (!$attr['image_width'] && method_exists($File, 'getWidth')) {
                $attr['image_width'] = $File->getWidth();
            }

            if (!$attr['image_height'] && method_exists($File, 'getHeight')) {
                $attr['image_height'] = $File->getHeight();
            }

            return $attr;
        }


        $list = [];

        foreach ($fileid as $id) {
            $id = $normalizeMediaId($id);

            if ($id === null) {
                continue;
            }

            if (Utils::isMediaUrl($id)) {
                $File = Utils::getMediaItemByUrl($id);
            } else {
                $File = $Media->get((int)$id);
            }

            if (!Utils::isImage($File)) {
                $list[] = $File->getAttributes();
                continue;
            }

            $attributes = $File->getAttributes();

            if (!$attributes['image_width'] && method_exists($File, 'getWidth')) {
                $attributes['image_width'] = $File->getWidth();
            }

            if (!$attributes['image_height'] && method_exists($File, 'getHeight')) {
                $attributes['image_height'] = $File->getHeight();
            }

            $list[] = $attributes;
        }

        return $list;
    },
    ['project', 'fileid'],
    'Permission::checkAdminUser'
);
