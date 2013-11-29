<?php

/**
 * This file contains \QUI\Groups\Manager
 */

namespace QUI\Groups;

/**
 * Group Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.groups
 */

class Manager extends \QUI\QDOM
{
    const TYPE_BOOL    = 1;
    const TYPE_TEXT    = 2;
    const TYPE_INT     = 3;
    const TYPE_VARCHAR = 4;

    /**
     * internal group cache
     * @var array
     */
    protected $_groups;

    /**
     * Files that are to be loaded in the admin area
     * @var array
     */
    protected $_adminjsfiles = array();

    /**
     * Return the db table for the groups
     *
     * @return String
     */
    static function Table()
    {
        return QUI_DB_PRFX .'groups';
    }

    /**
     * Setup for groups
     */
    public function setup()
    {
        $DataBase = \QUI::getDataBase();
        $Table    = $DataBase->Table();

        $Table->appendFields(self::Table(), array(
            'id'      => 'int(11) NOT NULL',
            'name'    => 'varchar(50) NOT NULL',
            'admin'   => 'tinyint(2) NOT NULL',
            'parent'  => 'int(11) NOT NULL',
            'active'  => 'tinyint(1) NOT NULL',
            'toolbar' => 'varchar(128) NULL',
            'rights'  => 'text'
        ));

        $Table->setPrimaryKey( self::Table(), 'id' );
        $Table->setIndex( self::Table(), 'parent' );
    }

    /**
     * Returns the first group
     *
     * @return \QUI\Groups\Manager
     */
    public function firstChild()
    {
        return $this->get(
            \QUI::conf( 'globals','root' )
        );
    }

    /**
     * Return a group by ID
     *
     * @param Integer $id - ID of the Group
     * @return \QUI\Groups\Manager
     *
     * @throws \QUI\Exception
     */
    public function get($id)
    {
        if ( !$id ) {
            throw new \QUI\Exception( 'Es wurde keine Gruppen ID übergeben' );
        }

        if ( isset( $this->_groups[ $id ] ) ) {
            return $this->_groups[ $id ];
        }

        $this->_groups[ $id ] = new \QUI\Groups\Group( $id );

        return $this->_groups[ $id ];
    }

    /**
     * Return the name of a group
     *
     * @param Integer $id - ID of the Group
     * @return String
     */
    public function getGroupNameById($id)
    {
        if ( !isset( $this->_groups[ $id ] ) ) {
            $this->_groups[ $id ] = $this->get( $id );
        }

        return $this->_groups[ $id ]->getAttribute( 'name' );
    }

    /**
     * Search / Scanns the groups
     *
     * @param array $params - \QUI\Database\DB params
     * @return array
     */
    public function search($params)
    {
        return $this->_search( $params );
    }

    /**
     * Count the groups
     *
     * @param array $params - \QUI\Database\DB params
     */
    public function count($params)
    {
        $params['count'] = true;

        unset( $params['limit'] );
        unset( $params['start'] );

        $result = $this->_search( $params );

        if ( isset( $result[0] ) && isset( $result[0]['count'] ) ) {
            return (int)$result[0]['count'];
        }

        return 0;
    }

    /**
     * Internal search helper
     *
     * @param Array $params
     * @return Array
     * @ignore
     */
    protected function _search($params)
    {
        $DataBase = \QUI::getDataBase();
        $params   = \QUI\Utils\Security\Orthos::clearArray( $params );

        $allowOrderFields = array(
            'id', 'name', 'admin', 'parent', 'active'
        );

        $allowSearchFields = array(
            'id'     => true,
            'name'   => true,
            'admin'  => true,
            'parent' => true,
            'active' => true
        );

        $max   = 10;
        $start = 0;

        $_fields = array(
            'from' => self::Table()
        );

        if ( isset( $params['count'] ) )
        {
            $_fields['count'] = array(
                'select' => 'id',
                'as'     => 'count'
            );
        }

        if ( isset( $params['limit'] ) ||
             isset( $params['start'] ) )
        {
            if ( isset( $params['limit'] ) ) {
                $max = (int)$params['limit'];
            }

            if ( isset( $params['start'] ) ) {
                $start = (int)$params['start'];
            }

            $_fields['limit'] = $start .', '. $max;
        }

        if ( isset( $params['order'] ) &&
             isset( $params['field'] ) &&
             $params['field'] &&
             in_array( $params['field'], $allowOrderFields ) )
        {
            $_fields['order'] = $params['field'] .' '. $params['order'];
        }

        if ( isset( $params['where'] ) ) {
            $_fields['where'] = $params['where'];
        }

        if ( isset( $params['search'] ) &&
             !isset( $params['searchfields'] ) )
        {
            $_fields['where'] = array(
                'name' => array(
                    'type'  => '%LIKE%',
                    'value' => $params['search']
                )
            );
        } else if (
            isset( $params['search'] ) &&
            isset( $params['searchfields'] ) &&
            is_array( $params['searchfields']) )
        {
            foreach ( $params['searchfields'] as $field )
            {
                if ( !isset( $allowSearchFields[ $field ] ) ) {
                    continue;
                }

                $_fields['where_or'][$field] = array(
                    'type'  => '%LIKE%',
                    'value' => $params['search']
                );
            }
        }

        return $DataBase->fetch( $_fields );
    }
}
