<?php

namespace QUI\Messages;

use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Schema;
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
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public static function setup(): void
    {
        $schemaManager = QUI::getSchemaManager();
        $schema = new Schema();
        $tableName = QUI::getDBTableName('messages');

        $table = $schema->createTable($tableName);
        $table->addColumn("uid", "string", ["length" => 50, "notnull" => false]);
        $table->addColumn("message", "text");
        $table->addColumn("mtype", "string", ["length" => 100, "notnull" => false]);
        $table->addColumn("mcode", "string", ["length" => 5, "notnull" => false]);
        $table->addColumn("mtime", "integer", ["notnull" => false]);
        $table->setPrimaryKey(["uid"]);

        if (!$schemaManager->tablesExist([$tableName])) {
            $schemaManager->createTable($table);
        }
    }

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
                case Attention::class:
                    $Message = new Attention([
                        'message' => $str
                    ]);
                    break;

                case Error::class:
                    $Message = new Error([
                        'message' => $str
                    ]);
                    break;

                case Information::class:
                    $Message = new Information([
                        'message' => $str
                    ]);
                    break;

                case Success::class:
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
     */
    public function addAttention(string $str): void
    {
        $this->addMessage(
            new Attention([
                'message' => $str
            ])
        );
    }

    public function addMessage(Message $Message): void
    {
        $this->messages[$Message->getHash()] = $Message;
    }

    /**
     * Add an error for a user
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
