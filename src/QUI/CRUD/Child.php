<?php

/**
 * This file contains QUI\CRUD\Child
 */

namespace QUI\CRUD;

use QUI;

use function array_key_exists;

/**
 * Class Element
 * Abstraction element for create-read-update-delete
 *
 * @event onDeleteBegin
 * @event onDeleteEnd
 * @event onSaveBegin
 * @event onSaveEnd
 */
abstract class Child extends QUI\QDOM
{
    protected QUI\Events\Event $Events;

    public function __construct(protected int|string $id, protected Factory $Factory)
    {
        $this->Events = new QUI\Events\Event();
    }

    /**
     * Load the data from the database
     *
     * @throws QUI\Database\Exception
     */
    public function refresh(): void
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
     * Return the Child-ID
     */
    public function getId(): int|string
    {
        return $this->id;
    }

    /**
     * Delete the CRUD child
     *
     * @throws QUI\ExceptionStack|QUI\Exception
     */
    public function delete(): void
    {
        $this->Events->fireEvent('deleteBegin');

        QUI::getDataBase()->delete(
            $this->Factory->getDataBaseTableName(),
            ['id' => $this->getId()]
        );


        $this->Events->fireEvent('deleteEnd');
    }

    /**
     * Update the CRUD child
     *
     * @throws QUI\ExceptionStack|QUI\Exception
     */
    public function update(): void
    {
        $this->Events->fireEvent('saveBegin');
        $this->Events->fireEvent('updateBegin');

        $needles = $this->Factory->getChildAttributes();
        $savedData = [];

        foreach ($needles as $needle) {
            if (!array_key_exists($needle, $this->attributes)) {
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

    /**
     * returns an attribute
     * if the attribute is not set, it returns false
     */
    public function getAttribute(string $name): mixed
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        return false;
    }
}
