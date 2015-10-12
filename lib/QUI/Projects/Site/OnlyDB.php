<?php

/**
 * This file contains the \QUI\Projects\Site\DB
 */

namespace QUI\Projects\Site;

use QUI;

/**
 * This object is only used to get data purely from the DataBase
 * Without performing file system operations (cache etc.)
 *
 * @author www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class OnlyDB extends QUI\Projects\Site
{
    /**
     * constructor
     *
     * @param \QUI\Projects\Project $Project
     * @param Integer               $id - Site ID
     *
     * @throws QUI\Exception
     */
    public function __construct(QUI\Projects\Project $Project, $id)
    {
        $this->_users = \QUI::getUsers();
        $this->_user = $this->_users->getUserBySession();

        $this->_TABLE = $Project->getAttribute('db_table');
        $this->_RELTABLE = $this->_TABLE.'_relations';
        $this->_RELLANGTABLE = $Project->getAttribute('name').'_multilingual';

        $id = (int)$id;

        if (empty($id)) {
            throw new QUI\Exception('Site Error; No ID given:'.$id, 400);
        }

        $this->_id = $id;

        // Daten aus der DB hohlen
        $this->refresh();


        // onInit event
        $this->Events->fireEvent('init', array($this));
        \QUI::getEvents()->fireEvent('siteInit', array($this));
    }

    /**
     * Hohlt sich die Daten frisch us der DB
     */
    public function refresh()
    {
        $result = QUI::getDataBase()->fetch(array(
            'from'  => $this->_TABLE,
            'where' => array(
                'id' => $this->getId()
            ),
            'limit' => '1'
        ));

        if (!isset($result[0])) {
            throw new QUI\Exception('Site not exist', 404);
        }

        // Verknüpfung hohlen
        if ($this->getId() != 1) {
            $relresult = \QUI::getDataBase()->fetch(array(
                'from'  => $this->_RELTABLE,
                'where' => array(
                    'child' => $this->getId()
                )
            ));

            if (isset($relresult[0])) {
                foreach ($relresult as $entry) {
                    if (!isset($entry['oparent'])) {
                        continue;
                    }

                    $this->_LINKED_PARENT = $entry['oparent'];
                }
            }
        }

        if (isset($result[0]['extra'])) /* deprecated */ {
            $this->_extra = json_decode($result[0]['extra'], true);
            unset($result[0]['extra']);
        }

        $this->setAttributes($result[0]);
    }

    /**
     * Clears the internal objects
     */
    public function __destroy()
    {
        $this->_users = null;
        $this->_user = null;
    }
}
