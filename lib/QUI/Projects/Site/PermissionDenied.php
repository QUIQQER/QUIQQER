<?php

/**
 * This file contains the \QUI\Projects\Site\DB
 */

namespace QUI\Projects\Site;

use QUI;

/**
 * This object is only used to if a Site is requested and the current user
 * has no permission to view this Site
 *
 * @author www.pcsg.de (Patrick Müller)
 * @licence For copyright and license information, please view the /README.md
 */
class PermissionDenied extends QUI\Projects\Site
{
    /**
     * constructor
     *
     * @param \QUI\Projects\Project $Project
     * @param integer $id - Site ID
     *
     * @throws QUI\Exception
     */
    public function __construct(QUI\Projects\Project $Project, $id)
    {
        $this->TABLE        = $Project->table();
        $this->RELTABLE     = $Project->table() . '_relations';
        $this->RELLANGTABLE = $Project->getAttribute('name') . '_multilingual';

        $id = (int)$id;

        if (empty($id)) {
            throw new QUI\Exception('Site Error; No ID given:' . $id, 400);
        }

        $this->id      = $id;
        $this->Events  = new QUI\Events\Event();
        $this->Project = $Project;

        // Get data from start page
        $this->refresh();

        // onInit event
        $this->Events->fireEvent('init', array($this));
        QUI::getEvents()->fireEvent('siteInit', array($this));
    }

    /**
     * @inheritdoc
     * @return QUI\Projects\Site
     */
    public function load($plugin = false)
    {
        return $this;
    }

    /**
     * Get data from start Site (ID: 1)
     *
     * @return void
     */
    public function refresh()
    {
        $result = QUI::getDataBase()->fetch(array(
            'from'  => $this->TABLE,
            'where' => array(
                'id' => 1
            ),
            'limit' => '1'
        ));

        $this->setAttributes($result[0]);
    }
}
