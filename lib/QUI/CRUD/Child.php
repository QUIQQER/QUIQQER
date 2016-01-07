<?php

/**
 * This file contains QUI\CRUD\Child
 */
namespace QUI\CRUD;

use QUI;

/**
 * Class Element
 * Abstration element for create-read-update-delete
 *
 * @package QUI\CRUD
 *
 * @event onDeleteBegin
 * @event onDeleteEnd
 * @event onSaveBegin
 * @event onSaveEnd
 */
abstract class Child extends QUI\QDOM
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var QUI\Events\Event
     */
    protected $Events;

    /**
     * @var Factory
     */
    protected $Factory;

    /**
     * Child constructor.
     *
     * @param integer $id
     * @param Factory $Factory
     */
    public function __construct($id, Factory $Factory)
    {
        $this->Events  = new QUI\Events\Event();
        $this->Factory = $Factory;
        $this->id      = (int)$id;
    }

    /**
     * Return the Child-ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Load the data from the database
     */
    public function refresh()
    {
        $data = $this->Factory->getChildrenData(array(
            'where' => array(
                'id' => $this->getId()
            )
        ));

        if (isset($data[0])) {
            $this->setAttributes($data[0]);
        }
    }

    /**
     * Delete the child
     *
     * @throws QUI\ExceptionStack|QUI\Exception
     */
    public function delete()
    {
        $this->Events->fireEvent('deleteBegin');

        QUI::getDataBase()->delete(
            $this->Factory->getTable(),
            array('id' => $this->getId())
        );


        $this->Events->fireEvent('deleteEnd');
    }

    /**
     * @throws QUI\ExceptionStack|QUI\Exception
     */
    public function update()
    {
        $this->Events->fireEvent('saveBegin');

        $needles   = $this->Factory->getChildAttributes();
        $savedData = array();

        foreach ($needles as $needle) {
            if (!$this->existsAttribute($needle)) {
                continue;
            }

            $savedData[$needle] = $this->getAttribute($needle);
        }

        QUI::getDataBase()->update(
            $this->Factory->getTable(),
            $savedData,
            array('id' => $this->getId())
        );

        $this->Events->fireEvent('saveEnd');
    }
}
