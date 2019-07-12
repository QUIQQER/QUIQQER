<?php

/**
 * This file contains QUI\CRUD\Child
 */

namespace QUI\CRUD;

use QUI;

/**
 * Class Element
 * Abstraction element for create-read-update-delete
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
     *
     * @throws QUI\Database\Exception
     */
    public function refresh()
    {
        $data = $this->Factory->getChildrenData([
            'where' => [
                'id' => $this->getId()
            ]
        ]);

        if (isset($data[0])) {
            $this->setAttributes($data[0]);
        }
    }

    /**
     * Delete the CRUD child
     *
     * @throws QUI\ExceptionStack|QUI\Exception
     */
    public function delete()
    {
        $this->Events->fireEvent('deleteBegin');

        QUI::getDataBase()->delete(
            $this->Factory->getDataBaseTableName(),
            ['id' => $this->getId()]
        );


        $this->Events->fireEvent('deleteEnd');
    }

    /**
     * returns a attribute
     * if the attribute is not set, it returns false
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getAttribute($name)
    {
        if (\array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        return false;
    }

    /**
     * Update the CRUD child
     *
     * @throws QUI\ExceptionStack|QUI\Exception
     */
    public function update()
    {
        $this->Events->fireEvent('saveBegin');
        $this->Events->fireEvent('updateBegin');

        $needles   = $this->Factory->getChildAttributes();
        $savedData = [];

        foreach ($needles as $needle) {
            if (!\array_key_exists($needle, $this->attributes)) {
                continue;
            }

            $savedData[$needle] = $this->getAttribute($needle);
        }

        QUI::getDataBase()->update(
            $this->Factory->getDataBaseTableName(),
            $savedData,
            ['id' => $this->getId()]
        );

        $this->Events->fireEvent('saveEnd');
        $this->Events->fireEvent('updateEnd');
    }
}
