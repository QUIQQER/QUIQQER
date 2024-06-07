<?php

namespace QUI\Users;

use QUI;
use QUI\Exception;
use QUI\ExceptionStack;

use function array_filter;

use const OPT_DIR;

/**
 * Is responsible for installing and customizing the databases for users and groups in the QUIQQER system.
 *
 * It contains routines that ensure that all necessary database structures and entries
 * for the administration of users and groups are created and updated correctly.
 */
class Install
{
    /**
     * User installation stuff
     */
    public static function user(): void
    {
        $DataBase = QUI::getDataBase();
        $table = QUI\Users\Manager::table();

        // Patch strict
        $DataBase->getPDO()->exec(
            "ALTER TABLE `$table` 
            CHANGE `lastedit` `lastedit` DATETIME NULL DEFAULT NULL,
            CHANGE `expire` `expire` DATETIME NULL DEFAULT NULL,
            CHANGE `password` `password` VARCHAR(255) NOT NULL DEFAULT '',
            CHANGE `birthday` `birthday` DATE NULL DEFAULT NULL;
            "
        );

        try {
            $DataBase->getPDO()->exec(
                "
                UPDATE `$table` 
                SET lastedit = NULL 
                WHERE 
                    lastedit = '0000-00-00 00:00:00' OR 
                    lastedit = '';

                UPDATE `$table` 
                SET expire = NULL 
                WHERE 
                    expire = '0000-00-00 00:00:00' OR 
                    expire = '';

                UPDATE `$table` 
                SET birthday = NULL 
                WHERE 
                    birthday = '0000-00-00' OR 
                    birthday = '';
            "
            );
        } catch (\Exception) {
        }
    }

    /**
     * group installation stuff
     *
     * @throws ExceptionStack
     * @throws Exception
     * @throws QUI\Database\Exception
     */
    public static function groups(): void
    {
        // read database xml, because we need the newest groups db
        $dbFields = QUI\Utils\Text\XML::getDataBaseFromXml(OPT_DIR . 'quiqqer/core/database.xml');
        unset($dbFields['projects']);

        $dbFields['globals'] = array_filter($dbFields['globals'], static function (array $entry): bool {
            return $entry['suffix'] === 'groups';
        });

        QUI\Utils\Text\XML::importDataBase($dbFields);

        $DataBase = QUI::getDataBase();
        $DataBase->execSQL(
            "ALTER TABLE `" . QUI\Groups\Manager::table() . "` CHANGE `parent` `parent` VARCHAR(50) NULL DEFAULT NULL;"
        );

        $Table = $DataBase->table();
        $Table->setPrimaryKey(QUI\Groups\Manager::table(), 'id');
        $Table->setIndex(QUI\Groups\Manager::table(), 'parent');


        // Guest
        $result = QUI::getDataBase()->fetch([
            'from' => QUI\Groups\Manager::table(),
            'where' => [
                'id' => 0
            ]
        ]);

        if (!isset($result[0])) {
            QUI\System\Log::addNotice('Guest Group does not exist.');

            QUI::getDataBase()->insert(QUI\Groups\Manager::table(), [
                'id' => 0,
                'uuid' => 0,
                'parent' => 0,
                'name' => 'Guest'
            ]);

            QUI\System\Log::addNotice('Guest Group was created.');
        } else {
            QUI::getDataBase()->update(QUI\Groups\Manager::table(), [
                'name' => 'Guest'
            ], [
                'id' => 0
            ]);

            QUI\System\Log::addNotice('Guest exists only updated');
        }


        // Everyone
        $result = QUI::getDataBase()->fetch([
            'from' => QUI\Groups\Manager::table(),
            'where' => [
                'id' => 1
            ]
        ]);

        if (!isset($result[0])) {
            QUI\System\Log::addNotice('Everyone Group does not exist...');

            QUI::getDataBase()->insert(QUI\Groups\Manager::table(), [
                'id' => 1,
                'uuid' => 1,
                'parent' => 0,
                'name' => 'Everyone'
            ]);

            QUI\System\Log::addNotice('Everyone Group was created.');
        } else {
            QUI::getDataBase()->update(QUI\Groups\Manager::table(), [
                'name' => 'Everyone'
            ], [
                'id' => 1
            ]);

            QUI\System\Log::addNotice('Everyone exists');
        }

        QUI::getUsers()->get(0)->save();
        QUI::getUsers()->get(5)->save();
        QUI::getUsers()->get(QUI::conf('globals', 'rootuser'))->save();
    }
}
