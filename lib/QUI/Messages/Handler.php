<?php

/**
 * This file contains \QUI\Messages\Handler
 */

namespace QUI\Messages;

use QUI;
use QUI\Users\User;
use QUI\Utils\Security\Orthos;

/**
 * Message Handler for QUIQQER
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Handler
{
    protected $_messages = array();

    /**
     * Return the message handler db table
     */
    static function Table()
    {
        return QUI_DB_PRFX.'messages';
    }

    /**
     * Create the database table for the messages
     */
    static function setup()
    {
        QUI::getDataBase()->Table()->appendFields(self::Table(), array(
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
        $this->_messages = array();
    }

    /**
     * Return all new messages for an user and delete it in the queue
     *
     * @param \QUI\Users\User|\QUI\Users\Nobody $User
     *
     * @return array
     */
    public function getNewMessages($User)
    {
        $result = $this->_messages;

        if (!$User->getId()) {
            return $result;
        }

        $list = QUI::getDataBase()->fetch(array(
            'from'  => self::Table(),
            'where' => array(
                'uid' => $User->getId()
            )
        ));

        QUI::getDataBase()->delete(self::Table(), array(
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

        $this->_messages = array();

        return $result;
    }

    /**
     * Return the messages list as pure array
     *
     * @param \QUI\Users\User|\QUI\Users\Nobody $User
     *
     * @return array
     */
    public function getMessagesAsArray($User)
    {
        $result = array();
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
        $this->_messages[$Message->getHash()] = $Message;
    }

    /**
     * Add an information for an user
     *
     * @param String $str
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
     * @param String $str
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
     * @param String $str
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
     * @param String $str
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
     * @param User                  $User
     * @param \QUI\Messages\Message $Message
     */
    public function sendMessage(User $User, Message $Message)
    {
        $message = $Message->getMessage();
        $message = Orthos::clearMySQL($message);

        QUI::getDataBase()->insert(self::Table(), array(
            'uid'     => $User->getId(),
            'message' => $message,
            'mcode'   => (int)$Message->getCode(),
            'mtime'   => (int)$Message->getAttribute('time'),
            'mtype'   => $Message->getType()
        ));
    }

    /**
     * Send an information to an user and save it to the database
     *
     * @param User   $User
     * @param String $str
     */
    public function sendAttention(User $User, $str)
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
     * @param User   $User
     * @param String $str
     */
    public function sendError(User $User, $str)
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
     * @param User   $User
     * @param String $str
     */
    public function sendInformation(User $User, $str)
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
     * @param User   $User
     * @param String $str
     */
    public function sendSuccess(User $User, $str)
    {
        $this->sendMessage(
            $User,
            new Success(array(
                'message' => $str
            ))
        );
    }
}
