<?php

/**
 * This file contains the Projects_Site_Edit
 */

/**
 * Trash from a Project
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.projects
 */

class Projects_Trash extends \QUI\QDOM implements Interface_Projects_Trash
{
    /**
     * The Project of the trash
     * @var Projects_Project
     */
    protected $_Project = null;

    /**
     * Konstruktor
     *
     * @param Projects_Project $Project
     */
    public function __construct(Projects_Project $Project)
    {
        $this->_Project = $Project;
    }

    /**
     * Get Sites from Trash
     *
     * @param Array $params - optional
     * - order
     * - sort
     *
     * - max
     * - page
     *
     * @return Array
     */
    public function getList($params=array())
    {
        // create grid
        $Grid = new Utils_Grid();

        if ( isset( $params['max'] ) ) {
            $Grid->setAttribute('max', (int)$params['max']);
        }

        if ( isset( $params['page'] ) ) {
            $Grid->setAttribute('page', (int)$params['page']);
        }

        $_params = $Grid->parseDBParams(array(
            'where' => array(
                'deleted' => 1,
                'active'  => -1
            )
        ));

        /**
         * Order and Sort
         */
        if ( isset( $params['order'] ) )
        {
            switch ( $params['order'] )
            {
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

        if ( isset( $params['sort'] ) )
        {
            switch ( $params['sort'] )
            {
                case 'ASC':
                    $_params['order'] = $_params['order'] .' ASC';
                break;

                default:
                    $_params['order'] = $_params['order'] .' DESC';
                break;
            }
        }

        /**
         * Creating result
         */
        $result = array();
        $sites  = $this->_Project->getSites( $_params );

        foreach ( $sites as $Site )
        {
            $result[] = array(
                'icon'  => URL_BIN_DIR .'16x16/page.png',
                'name'  => $Site->getAttribute('name'),
                'title' => $Site->getAttribute('title'),
                'type'  => $Site->getAttribute('type'),
                'id'    => $Site->getId()
            );
        }

        //\QUI\System\Log::writeRecursive( $result );

        $total = $this->_Project->getSites(array(
            'where' => array(
                'deleted' => 1,
                'active'  => -1
            ),
            'count' => true
        ));

        return $Grid->parseResult( $result, $total );
    }

    /**
     * Zerstört die gewünschten Seiten im Trash
     *
     * @param Projects_Project $Project
     * @param Array $ids
     */
    public function destroy(Projects_Project $Project, $ids=array())
    {
        if ( !is_array( $ids ) ) {
            return;
        }

        foreach ( $ids as $id )
        {
            $Site = new Projects_Site_Edit( $Project, (int)$id );
            $Site->destroy();
        }
    }

    /**
     * Stellt die gewünschten Seiten wieder her
     *
     * @param Projects_Project $Project
     * @param Array $ids
     * @param Integer $parentid
     */
    public function restore(Projects_Project $Project, $ids, $parentid)
    {
        $Parent = new Projects_Site_Edit( $Project, (int)$parentid );

        foreach ( $ids as $id )
        {
            $Site = new Projects_Site_Edit( $Project, $id );

            $Site->restore();
            $Site->move( $Parent->getId() );
            $Site->deactivate();
        }
    }
}

?>