<?php

/**
 * This file contains \QUI\Projects\Media\Trash
 */

namespace QUI\Projects\Media;

use QUI;
use QUI\Projects\Media;

/**
 * The media trash
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.projects.media
 * @licence For copyright and license information, please view the /README.md
 */
class Trash implements QUI\Interfaces\Projects\Trash
{
    /**
     * The media
     *
     * @var QUI\Projects\Media
     */
    protected $Media;

    /**
     * Konstruktor
     *
     * @param QUI\Projects\Media $Media
     */
    public function __construct(Media $Media)
    {
        $this->Media = $Media;

        QUI\Utils\System\File::mkdir($this->getPath());
    }

    /**
     * Returns the trash path for the Media
     *
     * @return string
     */
    public function getPath()
    {
        return VAR_DIR.'media/trash/'.$this->Media->getProject()->getName().'/';
    }

    /**
     * Returns the items in the trash
     *
     * @param array $params - QUI\Utils\Grid parameters
     *
     * @return array
     */
    public function getList($params = [])
    {
        $Grid  = new QUI\Utils\Grid();
        $query = $Grid->parseDBParams($params);

        $query['from']  = $this->Media->getTable();
        $query['where'] = [
            'deleted' => 1
        ];

        // count
        $count = QUI::getDataBase()->fetch([
            'from'  => $this->Media->getTable(),
            'count' => 'count',
            'where' => [
                'deleted' => 1
            ]
        ]);

        $data = QUI::getDataBase()->fetch($query);

        foreach ($data as $key => $entry) {
            $data[$key]['icon'] = Utils::getIconByExtension(
                Utils::getExtension($entry['file'])
            );

            $data[$key]['path'] = '---';

            $pathHistory = json_decode($entry['pathHistory'], true);

            if (!empty($pathHistory)) {
                $path = end($pathHistory);
                $path = dirname($path);

                $data[$key]['path'] = $path.'/';
            }
        }

        return $Grid->parseResult($data, $count[0]['count']);
    }

    /**
     * Destroy the file item from the filesystem
     * After it, its impossible to restore the item
     *
     * @param integer $id
     *
     * @throws QUI\Exception
     */
    public function destroy($id)
    {
        // check if the file is really deleted?
        $File = $this->Media->get($id);

        if (!$File->isDeleted()) {
            $File->delete();
        }

        $File->destroy();
    }

    /**
     * Clears the complete trash
     */
    public function clear()
    {
        $data = QUI::getDataBase()->fetch([
            'select' => 'id',
            'from'   => $this->Media->getTable(),
            'where'  => [
                'deleted' => 1
            ]
        ]);

        foreach ($data as $key => $entry) {
            try {
                $File = $this->Media->get($entry['id']);

                if (!$File->isDeleted()) {
                    continue;
                }

                $File->destroy();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addNotice($Exception->getMessage(), [
                    'method' => 'Media/Trash::clear()',
                    'fileId' => $entry['id']
                ]);
            }
        }
    }

    /**
     * Restore a item to a folder
     *
     * @param integer $id
     * @param QUI\Projects\Media\Folder $Folder
     *
     * @return QUI\Projects\Media\Item
     * @throws QUI\Exception
     */
    public function restore($id, Folder $Folder)
    {
        $file = $this->getPath().$id;

        if (!file_exists($file)) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.trash.file.not.found', [
                    'id' => $id
                ])
            );
        }

        // search old db entry for data
        $data = QUI::getDataBase()->fetch([
            'from'  => $this->Media->getTable(),
            'where' => [
                'id' => $id
            ],
            'limit' => 1
        ]);

        if (!isset($data[0])) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.trash.file.not.found')
            );
        }


        // rename the file for upload
        $extension = QUI\Utils\System\File::getEndingByMimeType(
            $data[0]['mime_type']
        );

        $newFile = $this->getPath().$data[0]['name'].$extension;

        QUI\Utils\System\File::move($file, $newFile);

        // insert the file
        $Item = $Folder->uploadFile($newFile);

        // change old db entry, if one exist
        $Item->setAttributes([
            'title' => $data[0]['title'],
            'alt'   => $data[0]['alt'],
            'short' => $data[0]['short']
        ]);

        $Item->save();

        // delete the old db entry
        QUI::getDataBase()->delete(
            $this->Media->getTable(),
            ['id' => $id]
        );

        return $Item;
    }
}
