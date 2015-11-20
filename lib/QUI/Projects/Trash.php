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
    protected $_Project = null;

    /**
     * Konstruktor
     *
     * @param \QUI\Projects\Project $Project
     */
    public function __construct(Project $Project)
    {
        $this->_Project = $Project;
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
    public function getList($params = array())
    {
        // create grid
        $Grid = new QUI\Utils\Grid();

        $_params = $Grid->parseDBParams($params);

        $_params['where'] = array(
            'deleted' => 1
        );


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
        $result = array();
        $sites = $this->_Project->getSites($_params);

        foreach ($sites as $Site) {
            /* @var $Site Site */
            $result[] = array(
                'icon'  => URL_BIN_DIR.'16x16/page.png',
                'name'  => $Site->getAttribute('name'),
                'title' => $Site->getAttribute('title'),
                'type'  => $Site->getAttribute('type'),
                'id'    => $Site->getId()
            );
        }

        //\QUI\System\Log::writeRecursive( $result );

        $total = $this->_Project->getSites(array(
            'where' => array(
                'deleted' => 1
            ),
            'count' => true
        ));

        return $Grid->parseResult($result, (int)$total);
    }

    /**
     * Zerstört die gewünschten Seiten im Trash
     *
     * @param array                 $ids
     */
    public function destroy($ids = array())
    {
        if (!is_array($ids)) {
            return;
        }

        foreach ($ids as $id) {
            $Site = new Site\Edit($this->_Project, (int)$id);
            $Site->destroy();
        }
    }

    /**
     * Clear complete trash
     */
    public function clear()
    {
        $ids = $this->_Project->getSitesIds(array(
            'where' => array(
                'deleted' => 1
            )
        ));

        foreach ($ids as $data) {
            $Site = new Site\Edit($this->_Project, (int)$data['id']);
            $Site->destroy();
        }
    }

    /**
     * Stellt die gewünschten Seiten wieder her
     *
     * @param \QUI\Projects\Project $Project
     * @param array                 $ids
     * @param integer               $parentid
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
