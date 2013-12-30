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
    protected $_messages = array();

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
        \QUI::getDataBase()->Table()->appendFields(self::Table(), array(
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
     * @param \QUI\Users\User|\QUI\Users\Nobody $User
     * @return array
     */
    public function getNewMessages($User)
    {
        $result = $this->_messages;

        if ( $User->getId() ) {
             return $result;
        }

        $list = \QUI::getDataBase()->fetch(array(
            'from'  => self::Table(),
            'where' => array(
                'uid' => $User->getId()
            )
        ));

        \QUI::getDataBase()->delete(self::Table(), array(
            'uid' => $User->getId()
        ));

        foreach ( $list as $entry )
        {
            $str = $entry['message'];

            switch ( $entry )
            {
                case 'QUI\\Messages\\Attention':
                    $Message = new \QUI\Messages\Attention(array(
                        'message' => $str
                    ));
                break;

                case 'QUI\\Messages\\Error':
                    $Message = new \QUI\Messages\Error(array(
                        'message' => $str
                    ));
                break;

                case 'QUI\\Messages\\Information':
                    $Message = new \QUI\Messages\Information(array(
                        'message' => $str
                    ));
                break;

                case 'QUI\\Messages\\Success':
                    $Message = new \QUI\Messages\Success(array(
                        'message' => $str
                    ));
                break;

                default:
                    $Message = new \QUI\Messages\Message(array(
                        'message' => $str
                    ));
            }

            $result[] = $Message;
        }

        $this->_messages = array();

        return $result;
    }

    /**
     * Return the messages list as pure array
     *
     * @param \QUI\Users\User|\QUI\Users\Nobody $User
     * @return array
     */
    public function getMessagesAsArray($User)
    {
        $result   = array();
        $messages = $this->getNewMessages( $User );

        foreach ( $messages as $Message ) {
            $result[] = $Message->getAttributes();
        }

        return $result;
    }

    /**
     * Add a message to the handler
     *
     * @param \QUi\Messages\Message $Message
     */
    public function addMessage($Message)
    {
        $this->_messages[] = $Message;
    }

    /**
     * Add an information for an user
     *
     * @param String $str
     */
    public function addAttention($str)
    {
        $this->addMessage(
            new \QUI\Messages\Attention(array(
                'message' => $str
            ))
        );
    }

    /**
     * Add an error for an user
     *
     * @param String $str
     */
    public function addError($str)
    {
        $this->addMessage(
            new \QUI\Messages\Error(array(
                'message' => $str
            ))
        );
    }

    /**
     * Add a information for an user
     *
     * @param String $str
     */
    public function addInformation($str)
    {
        $this->addMessage(
            new \QUI\Messages\Information(array(
                'message' => $str
            ))
        );
    }

    /**
     * Add a success message for an user
     *
     * @param String $str
     */
    public function addSuccess($str)
    {
        $this->addMessage(
            new \QUI\Messages\Success(array(
                'message' => $str
            ))
        );
    }

    /**
     * Send a message to an user and save it to the database
     *
     * @param \QUI\Users\User $User
     * @param \QUi\Messages\Message $Message
     */
    public function sendMessage(\QUI\Users\User $User, \QUI\Messages\Message $Message)
    {
        $message = $Message->getMessage();
        $message = \QUI\Utils\Security\Orthos::clearMySQL( $message );

        \QUI::getDataBase()->insert(self::Table(), array(
            'uid'     => $User->getId(),
            'message' => $message,
            'mcode'   => (int)$Message->getCode(),
            'mtime'   => (int)$Message->getAttribute('time'),
            'mtype'   => get_class( $Message )
        ));
    }

    /**
     * Send an information to an user and save it to the database
     *
     * @param \QUI\Users\User $User
     * @param String $str
     */
    public function sendAttention(\QUI\Users\User $User, $str)
    {
        $this->sendMessage(
            $User,
            new \QUI\Messages\Attention(array(
                'message' => $str
            ))
        );
    }

    /**
     * Send an error to an user and save it to the database
     *
     * @param \QUI\Users\User $User
     * @param String $str
     */
    public function sendError(\QUI\Users\User $User, $str)
    {
        $this->sendMessage(
            $User,
            new \QUI\Messages\Error(array(
                'message' => $str
            ))
        );
    }

    /**
     * Send a information to an user and save it to the database
     *
     * @param \QUI\Users\User $User
     * @param String $str
     */
    public function sendInformation(\QUI\Users\User $User, $str)
    {
        $this->sendMessage(
            $User,
            new \QUI\Messages\Information(array(
                'message' => $str
            ))
        );
    }

    /**
     * Send a success message to an user and save it to the database
     *
     * @param \QUI\Users\User $User
     * @param String $str
     */
    public function sendSuccess(\QUI\Users\User $User, $str)
    {
        $this->sendMessage(
            $User,
            new \QUI\Messages\Success(array(
                'message' => $str
            ))
        );
    }
}
