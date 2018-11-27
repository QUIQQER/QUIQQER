<?php

/**
 * This file contains \QUI\Messages\Handler
 */

namespace QUI\Messages;

use QUI;

/**
 * Message Handler for QUIQQER
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Handler
{
    protected $messages = array();

    /**
     * Return the message handler db table
     */
    public static function table()
    {
        return QUI::getDBTableName('messages');
    }

    /**
     * Create the database table for the messages
     */
    public static function setup()
    {
        QUI::getDataBase()->table()->addColumn(self::table(), array(
            'uid'     => 'int(11)',
            'message' => 'text',
            'mtype'   => 'varchar(100)',
            'mcode'   => 'varchar(5)',
            'mtime'   => 'int(11)'
        ));
    }

    /**
     * Clears the current message list
     */
    public function clear()
    {
        $this->messages = array();
    }

    /**
     * Return all new messages for an user and delete it in the queue
     *
     * @param QUI\Interfaces\Users\User $User
     *
     * @return array
     */
    public function getNewMessages(QUI\Interfaces\Users\User $User)
    {
        $result = $this->messages;

        if (!$User->getId()) {
            return $result;
        }

        $list = QUI::getDataBase()->fetch(array(
            'from'  => self::table(),
            'where' => array(
                'uid' => $User->getId()
            )
        ));

        QUI::getDataBase()->delete(self::table(), array(
            'uid' => $User->getId()
        ));

        foreach ($list as $entry) {
            $str = $entry['message'];

            switch ($entry['mtype']) {
                case 'QUI\\Messages\\Attention':
                    $Message = new Attention(array(
                        'message' => $str
                    ));
                    break;

                case 'QUI\\Messages\\Error':
                    $Message = new Error(array(
                        'message' => $str
                    ));
                    break;

                case 'QUI\\Messages\\Information':
                    $Message = new Information(array(
                        'message' => $str
                    ));
                    break;

                case 'QUI\\Messages\\Success':
                    $Message = new Success(array(
                        'message' => $str
                    ));
                    break;

                default:
                    $Message = new Message(array(
                        'message' => $str
                    ));
            }

            $result[] = $Message;
        }

        $this->messages = array();

        return $result;
    }

    /**
     * Return the messages list as pure array
     *
     * @param QUI\Interfaces\Users\User $User
     *
     * @return array
     */
    public function getMessagesAsArray(QUI\Interfaces\Users\User $User)
    {
        $result   = array();
        $messages = $this->getNewMessages($User);

        /* @var $Message Message */
        foreach ($messages as $Message) {
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
        $this->messages[$Message->getHash()] = $Message;
    }

    /**
     * Add an information for an user
     *
     * @param string $str
     */
    public function addAttention($str)
    {
        $this->addMessage(
            new Attention(array(
                'message' => $str
            ))
        );
    }

    /**
     * Add an error for an user
     *
     * @param string $str
     */
    public function addError($str)
    {
        $this->addMessage(
            new Error(array(
                'message' => $str
            ))
        );
    }

    /**
     * Add a information for an user
     *
     * @param string $str
     */
    public function addInformation($str)
    {
        $this->addMessage(
            new Information(array(
                'message' => $str
            ))
        );
    }

    /**
     * Add a success message for an user
     *
     * @param string $str
     */
    public function addSuccess($str)
    {
        $this->addMessage(
            new Success(array(
                'message' => $str
            ))
        );
    }

    /**
     * Send a message to an user and save it to the database
     *
     * @param QUI\Interfaces\Users\User $User
     * @param \QUI\Messages\Message $Message
     */
    public function sendMessage(QUI\Interfaces\Users\User $User, Message $Message)
    {
        if (QUI::getUsers()->isSystemUser($User) ||
            QUI::getUsers()->isNobodyUser($User)
        ) {
            return;
        }

        QUI::getDataBase()->insert(self::table(), array(
            'uid'     => $User->getId(),
            'message' => $Message->getMessage(),
            'mcode'   => (int)$Message->getCode(),
            'mtime'   => (int)$Message->getAttribute('time'),
            'mtype'   => $Message->getType()
        ));
    }

    /**
     * Send an information to an user and save it to the database
     *
     * @param QUI\Interfaces\Users\User $User
     * @param string $str
     */
    public function sendAttention(QUI\Interfaces\Users\User $User, $str)
    {
        $this->sendMessage(
            $User,
            new Attention(array(
                'message' => $str
            ))
        );
    }

    /**
     * Send an error to an user and save it to the database
     *
     * @param QUI\Interfaces\Users\User $User
     * @param string $str
     */
    public function sendError(QUI\Interfaces\Users\User $User, $str)
    {
        $this->sendMessage(
            $User,
            new Error(array(
                'message' => $str
            ))
        );
    }

    /**
     * Send a information to an user and save it to the database
     *
     * @param QUI\Interfaces\Users\User $User
     * @param string $str
     */
    public function sendInformation(QUI\Interfaces\Users\User $User, $str)
    {
        $this->sendMessage(
            $User,
            new Information(array(
                'message' => $str
            ))
        );
    }

    /**
     * Send a success message to an user and save it to the database
     *
     * @param QUI\Interfaces\Users\User $User
     * @param string $str
     */
    public function sendSuccess(QUI\Interfaces\Users\User $User, $str)
    {
        $this->sendMessage(
            $User,
            new Success(array(
                'message' => $str
            ))
        );
    }
}
