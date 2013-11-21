<?php

/**
 * This file contains \QUI\Messages\Handler
 */

namespace QUI\Messages;

/**
 * Message Handler for QUIQQER
 * @author www.pcsg.de (Henning Leutz)
 */

class Handler
{
    /**
     * Return the message handler db table
     */
    static function Table()
    {
        return QUI_DB_PRFX .'messages';
    }

    /**
     * Create the database table for the messages
     */
    static function setup()
    {
        \QUI::getDB()->createTableFields(self::Table(), array(
            'uid'     => 'int(11)',
            'message' => 'text',
            'mtype'   => 'varchar(100)',
            'mcode'   => 'varchar(5)',
            'mtime'   => 'int(11)'
        ));
    }

    /**
     * Return all new messages for an user and delete it in the queue
     *
     * @param \QUI\Users\User $User
     * @return array
     */
    public function getNewMessages(\QUI\Users\User $User)
    {
        $list = \QUI::getDB()->select(array(
            'from'  => self::Table(),
            'where' => array(
                'uid' => $User->getId()
            )
        ));

        \QUI::getDB()->deleteData(self::Table(), array(
            'uid' => $User->getId()
        ));

        return $list;
    }

    /**
     * Return the messages list as pure array
     *
     * @param \QUI\Users\User $User
     * @return array
     */
    public function getMessagesAsArray(\QUI\Users\User $User)
    {
        $result   = array();
        $messages = $this->getNewMessages( $User );

        foreach ( $messages as $Message ) {
            $result[] = $Message->toArray();
        }

        return $result;
    }

    /**
     * Send a message to an user
     *
     * @param \QUI\Users\User $User
     * @param \QUi\Messages\Message $Message
     */
    public function addMessage(\QUI\Users\User $User, \QUI\Messages\Message $Message)
    {
        $message = $Message->getMessage();
        $message = \QUI\Utils\Security\Orthos::clearMySQL( $message );

        \QUI::getDB()->addData(self::Table(), array(
            'uid'     => $User->getId(),
            'message' => $message,
            'mcode'   => (int)$Message->getCode(),
            'mtime'   => (int)$Message->getAttribute('time'),
            'mtype'   => get_class( $Message )
        ));
    }

    /**
     * Add an information for an user
     *
     * @param \QUI\Users\User $User
     * @param String $str
     */
    public function addAttention(\QUI\Users\User $User, $str)
    {
        $this->addMessage(
            $User,
            new \QUI\Messages\Attention(array(
                'message' => $str
            ))
        );
    }

    /**
     * Add an error for an user
     *
     * @param \QUI\Users\User $User
     * @param String $str
     */
    public function addError(\QUI\Users\User $User, $str)
    {
        $this->addMessage(
            $User,
            new \QUI\Messages\Error(array(
                'message' => $str
            ))
        );
    }

    /**
     * Add a information for an user
     *
     * @param \QUI\Users\User $User
     * @param String $str
     */
    public function addInformation(\QUI\Users\User $User, $str)
    {
        $this->addMessage(
            $User,
            new \QUI\Messages\Information(array(
                'message' => $str
            ))
        );
    }

    /**
     * Add a success message for an user
     *
     * @param \QUI\Users\User $User
     * @param String $str
     */
    public function addSuccess(\QUI\Users\User $User, $str)
    {
        $this->addMessage(
            $User,
            new \QUI\Messages\Success(array(
                'message' => $str
            ))
        );
    }
}
