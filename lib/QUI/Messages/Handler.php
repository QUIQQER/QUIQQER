<?php

/**
 * This file contains \QUI\Messages\Handler
 */

namespace QUI\Messages;

use Exception;
use QUI;

/**
 * Message Handler for QUIQQER
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Handler
{
    protected array $messages = [];

    /**
     * Create the database table for the messages
     */
    public static function setup(): void
    {
        try {
            QUI::getDataBase()->table()->addColumn(self::table(), [
                'uid' => 'int(11)',
                'message' => 'text',
                'mtype' => 'varchar(100)',
                'mcode' => 'varchar(5)',
                'mtime' => 'int(11)'
            ]);
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * Return the message handler db table
     */
    public static function table(): string
    {
        return QUI::getDBTableName('messages');
    }

    /**
     * Clears the current message list
     */
    public function clear(): void
    {
        $this->messages = [];
    }

    /**
     * Return the messages list as pure array
     *
     * @param QUI\Interfaces\Users\User $User
     *
     * @return array
     */
    public function getMessagesAsArray(QUI\Interfaces\Users\User $User): array
    {
        $result = [];
        $messages = $this->getNewMessages($User);

        /* @var $Message Message */
        foreach ($messages as $Message) {
            $result[] = $Message->getAttributes();
        }

        return $result;
    }

    /**
     * Return all new messages for a user and delete it in the queue
     *
     * @param QUI\Interfaces\Users\User $User
     *
     * @return array
     */
    public function getNewMessages(QUI\Interfaces\Users\User $User): array
    {
        $result = $this->messages;

        if (!$User->getUUID()) {
            return $result;
        }

        try {
            $list = QUI::getDataBase()->fetch([
                'from' => self::table(),
                'where' => [
                    'uid' => $User->getUUID()
                ]
            ]);

            QUI::getDataBase()->delete(self::table(), [
                'uid' => $User->getUUID()
            ]);
        } catch (QUI\Database\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return [];
        }

        foreach ($list as $entry) {
            $str = $entry['message'];

            switch ($entry['mtype']) {
                case 'QUI\\Messages\\Attention':
                    $Message = new Attention([
                        'message' => $str
                    ]);
                    break;

                case 'QUI\\Messages\\Error':
                    $Message = new Error([
                        'message' => $str
                    ]);
                    break;

                case 'QUI\\Messages\\Information':
                    $Message = new Information([
                        'message' => $str
                    ]);
                    break;

                case 'QUI\\Messages\\Success':
                    $Message = new Success([
                        'message' => $str
                    ]);
                    break;

                default:
                    $Message = new Message([
                        'message' => $str
                    ]);
            }

            $result[] = $Message;
        }

        $this->messages = [];

        return $result;
    }

    /**
     * Add an information for a user
     *
     * @param string $str
     */
    public function addAttention(string $str): void
    {
        $this->addMessage(
            new Attention([
                'message' => $str
            ])
        );
    }

    /**
     * Add a message to the handler
     *
     * @param Message $Message
     */
    public function addMessage(Message $Message): void
    {
        $this->messages[$Message->getHash()] = $Message;
    }

    /**
     * Add an error for a user
     *
     * @param string $str
     */
    public function addError(string $str): void
    {
        $this->addMessage(
            new Error([
                'message' => $str
            ])
        );
    }

    /**
     * Add an information for a user
     *
     * @param string $str
     */
    public function addInformation(string $str): void
    {
        $this->addMessage(
            new Information([
                'message' => $str
            ])
        );
    }

    /**
     * Add a success message for a user
     *
     * @param string $str
     */
    public function addSuccess(string $str): void
    {
        $this->addMessage(
            new Success([
                'message' => $str
            ])
        );
    }

    /**
     * Send an information to a user and save it to the database
     *
     * @param QUI\Interfaces\Users\User $User
     * @param string $str
     */
    public function sendAttention(QUI\Interfaces\Users\User $User, string $str): void
    {
        $this->sendMessage(
            $User,
            new Attention([
                'message' => $str
            ])
        );
    }

    /**
     * Send a message to a user and save it to the database
     *
     * @param QUI\Interfaces\Users\User $User
     * @param Message $Message
     */
    public function sendMessage(QUI\Interfaces\Users\User $User, Message $Message): void
    {
        if (
            QUI::getUsers()->isSystemUser($User) ||
            QUI::getUsers()->isNobodyUser($User)
        ) {
            return;
        }

        try {
            QUI::getDataBase()->insert(self::table(), [
                'uid' => $User->getUUID(),
                'message' => $Message->getMessage(),
                'mcode' => (int)$Message->getCode(),
                'mtime' => (int)$Message->getAttribute('time'),
                'mtype' => $Message->getType()
            ]);
        } catch (QUI\Database\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * Send an error to a user and save it to the database
     *
     * @param QUI\Interfaces\Users\User $User
     * @param string $str
     */
    public function sendError(QUI\Interfaces\Users\User $User, string $str): void
    {
        $this->sendMessage(
            $User,
            new Error([
                'message' => $str
            ])
        );
    }

    /**
     * Send an information to a user and save it to the database
     *
     * @param QUI\Interfaces\Users\User $User
     * @param string $str
     */
    public function sendInformation(QUI\Interfaces\Users\User $User, string $str): void
    {
        $this->sendMessage(
            $User,
            new Information([
                'message' => $str
            ])
        );
    }

    /**
     * Send a success message to a user and save it to the database
     *
     * @param QUI\Interfaces\Users\User $User
     * @param string $str
     */
    public function sendSuccess(QUI\Interfaces\Users\User $User, string $str): void
    {
        $this->sendMessage(
            $User,
            new Success([
                'message' => $str
            ])
        );
    }
}
