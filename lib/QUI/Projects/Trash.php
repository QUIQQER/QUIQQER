<?php

/**
 * This file contains the \QUI\Projects\Trash
 */

namespace QUI\Projects;

use QUI;

/**
 * Trash from a Project
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.projects
 * @licence For copyright and license information, please view the /README.md
 */
class Trash extends QUI\QDOM implements QUI\Interfaces\Projects\Trash
{
    /**
     * The Project of the trash
     *
     * @var \QUI\Projects\Project
     */
    protected $Project = null;

    /**
     * Konstruktor
     *
     * @param \QUI\Projects\Project $Project
     */
    public function __construct(Project $Project)
    {
        $this->Project = $Project;
    }

    /**
     * Get Sites from Trash
     *
     * @param array $params - optional
     *                      - order
     *                      - sort
     *
     * - max
     * - page
     *
     * @return array
     */
    public function getList($params = [])
    {
        // create grid
        $Grid = new QUI\Utils\Grid();

        $_params = $Grid->parseDBParams($params);

        $_params['where'] = [
            'active'  => -1,
            'deleted' => 1
        ];


        /**
         * Order and Sort
         */
        if (isset($params['order'])) {
            switch ($params['order']) {
                case 'name':
                case 'title':
                case 'type':
                    $_params['order'] = $params['order'];
                    break;

                default:
                    $_params['order'] = 'id';
                    break;
            }
        }

        if (isset($params['sort'])) {
            switch ($params['sort']) {
                case 'ASC':
                    $_params['order'] = $_params['order'].' ASC';
                    break;

                default:
                    $_params['order'] = $_params['order'].' DESC';
                    break;
            }
        }

        /**
         * Creating result
         */
        $result = [];
        $sites  = $this->Project->getSites($_params);

        foreach ($sites as $Site) {
            try {
                /* @var $Site Site */
                $result[] = [
                    'icon'  => URL_BIN_DIR.'16x16/page.png',
                    'name'  => $Site->getAttribute('name'),
                    'title' => $Site->getAttribute('title'),
                    'type'  => $Site->getAttribute('type'),
                    'id'    => $Site->getId(),
                    'path'  => $Site->getUrlRewritten()
                ];
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        $total = $this->Project->getSites([
            'where' => [
                'active'  => -1,
                'deleted' => 1
            ],
            'count' => true
        ]);

        return $Grid->parseResult($result, (int)$total);
    }

    /**
     * Zerstört die gewünschten Seiten im Trash
     *
     * @param array $ids
     */
    public function destroy($ids = [])
    {
        if (!\is_array($ids)) {
            return;
        }

        foreach ($ids as $id) {
            $Site = new Site\Edit($this->Project, (int)$id);
            $Site->destroy();
        }
    }

    /**
     * Clear complete trash
     */
    public function clear()
    {
        $ids = $this->Project->getSitesIds([
            'where' => [
                'deleted' => 1
            ]
        ]);

        foreach ($ids as $data) {
            $Site = new Site\Edit($this->Project, (int)$data['id']);
            $Site->destroy();
        }
    }

    /**
     * Stellt die gewünschten Seiten wieder her
     *
     * @param \QUI\Projects\Project $Project
     * @param array $ids
     * @param integer $parentid
     *
     * @throws QUI\Exception
     */
    public function restore(Project $Project, $ids, $parentid)
    {
        $Parent = new Site\Edit($Project, (int)$parentid);

        foreach ($ids as $id) {
            $Site = new Site\Edit($Project, $id);

            $Site->restore();
            $Site->move($Parent->getId());
            $Site->deactivate();
        }
    }
}
