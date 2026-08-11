<?php

/**
 * This file contains the \QUI\Utils\MyDB
 */

namespace QUI\Utils;

use QUI;
use QUI\Database\Exception;

/**
 * Bridge für die alte MyDB Klasse zu neuer \PDO
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 *
 * @deprecated
 */
class MyDB implements \Stringable
{
    /**
     * internal db object
     */
    protected \QUI\Database\DB $DB;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->DB = new \QUI\Database\DB([
            'doctrine' => QUI::getDataBaseConnection()
        ]);
    }

    /**
     * \QUI\Database\DB Objekt (Neues Datenbank Objekt)
     */
    public function getUtilsDB(): \QUI\Database\DB
    {
        return $this->DB;
    }

    /**
     * Tostring Magic
     */
    public function __toString(): string
    {
        return 'MyDB()';
    }

    /**
     * Schließe die MySQL Verbindung
     *
     * @return void
     */
    public function close()
    {
    }

    /**
     * Maskiert die MySQL Query
     *
     * @param string $data
     *
     * @return string
     */
    public function escape($data)
    {
        if (!is_numeric($data)) {
            $data = $this->getPDO()->quote($data);
        }

        return $data;
    }

    public function getPDO(): ?\PDO
    {
        // MyDB is the deprecated compatibility bridge and must keep returning PDO for legacy consumers.
        // nosemgrep: quiqqer.forbid-legacy-database-access
        return QUI::getPDO();
    }

    /**
     * MASKIERTE QUERY
     *
     * @param mixed $query
     *
     * @return array<int, array<int|string, mixed>>
     *
     * @throws \QUI\Exception
     *
     * @deprecated use PDO and prepared statemens
     * getPDO()->query()->fetch
     * getPDO()->query()->fetchAll
     * getPDO()->exec()
     */
    public function query($query)
    {
        if (!is_string($query)) {
            throw new QUI\Exception('only strings accepted');
        }

        $query .= ';';
        $Statement = $this->getPDO()->query($query);

        if ($Statement === false) {
            throw new QUI\Exception('Could not execute database query.');
        }

        return $Statement->fetchAll();
    }

    /**
     * MySQL Select
     *
     * @param array<string, mixed> $params
     *                      from => string table
     *                      select => string table
     *                      count => count | true oder AS Angabe
     *                      where => string where
     *                      => Array
     *                      order => string order
     *                      group => string group
     * @param string $type - BOTH, NUM, ASSOC, OBJ
     * @param string $type2 - ARRAY, ROW
     *
     * @return array<int|string, mixed>|object
     */
    public function select(array $params, $type = "ARRAY", $type2 = 'ARRAY')
    {
        return $this->getData($params, $type, $type2);
    }

    /**
     * Liefert Daten aus der Datenbank im Typ ARRAY oder ROW oder OBJEKT
     *
     * @param array<string, mixed> $params
     * @param string $type = BOTH, NUM, ASSOC, OBJ
     * @param string $qtype = BOTH, NUM, ASSOC
     *
     * @return object|array<int|string, mixed>
     */
    public function getData($params, $type = 'ARRAY', $qtype = "NUM")
    {
        switch ($type) {
            case 'OBJ':
                return $this->DB->fetch($params, \PDO::FETCH_OBJ);

            case 'NUM':
                return $this->DB->fetch($params, \PDO::FETCH_NUM);

            case 'BOTH':
                return $this->DB->fetch($params, \PDO::FETCH_BOTH);

            default:
            case 'ASSOC':
                return $this->DB->fetch($params, \PDO::FETCH_ASSOC);
        }
    }

    /**
     * Unmaskierte Query
     *
     * @param array<string, mixed> $params
     *
     * @return \PDOStatement
     */
    public function queryNoEscape($params): \PDOStatement
    {
        return $this->DB->exec($params);
    }

    /**
     * gibt alle felder zurück
     *
     * @param string $table
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFields($table)
    {
        return $this->DB->table()->getColumns($table);
    }

    /**
     * Gibt die Tabellen zurück
     *
     * @return string[]
     */
    public function getTables()
    {
        return $this->DB->table()->getTables();
    }

    /**
     * tabelle, name, 'email'=>'horst@desgibbetnet.net'),array('id'=>12)
     * oder
     * tabelle, name, 'email'=>'horst@desgibbetnet.net'),"id=12 AND nachname = 'Meier'"
     *
     * @param string $table
     * @param string $field
     * @param string|array<string, mixed> $fieldAndId
     *
     * @return array<int|string, mixed>|object
     */
    public function getOneData($table, $field, $fieldAndId)
    {
        return $this->getData([
            'select' => $field,
            'from' => $table,
            'where' => $fieldAndId
        ]);
    }

    /**
     * tabelle, array('name'=>'Horst', 'email'=>'horst@desgibbetnet.net'),array('id'=>12)
     * oder
     * tabelle, array('name'=>'Horst', 'email'=>'horst@desgibbetnet.net'),"id=12 AND nachname = 'Meier'"
     *
     * @param string $table
     * @param array<string, mixed> $fieldValue
     * @param string|array<string, mixed> $fieldAndId
     *
     * @return \PDOStatement
     */
    public function updateData($table, $fieldValue, $fieldAndId): \PDOStatement
    {
        return $this->DB->exec([
            'update' => $table,
            'set' => $fieldValue,
            'where' => $fieldAndId
        ]);
    }

    /**
     * @param string $table
     * @param array<string, mixed> $fieldValue
     *
     * @return int|string|false
     */
    public function insertData($table, $fieldValue)
    {
        return $this->addData($table, $fieldValue);
    }

    /**
     * add a data row
     *
     * @param string $table
     * @param array<string, mixed> $FieldValue - [array('field1'=>'value1', 'field2'=>'value2', 'field3'=>'value3')]
     *
     * @return int|string|false
     */
    public function addData($table, $FieldValue)
    {
        return $this->insert([
            'insert' => $table,
            'set' => $FieldValue
        ]);
    }

    /**
     * Insert Query mit Rückgabe (lastInsertId)
     *
     * @param array<string, mixed> $params
     *
     * @return int|string|false
     */
    public function insert($params)
    {
        $this->DB->exec($params);

        return $this->DB->getPDO()->lastInsertId();
    }

    /**
     * tabelle , array('id'=>1) oder string "id=1 AND name = 'Horst'"
     *
     * @param string $table
     * @param string|array<string, mixed> $fieldAndId
     *
     * @return \PDOStatement
     * @throws Exception
     */
    public function deleteData($table, $fieldAndId): \PDOStatement
    {
        return $this->DB->exec([
            'delete' => true,
            'from' => $table,
            'where' => $fieldAndId
        ]);
    }

    /**
     * Optimiert Tabellen
     *
     * @param string|string[] $tables
     */
    public function optimize($tables): void
    {
        $this->DB->table()->optimize($tables);
    }

    /**
     * Enter description here...
     *
     * @param string $table
     * @param array<string, mixed> $fields
     */
    public function createTable($table, $fields): void
    {
        $this->DB->table()->create($table, $fields);
    }

    /**
     * Erweitert Tabellen mit den Feldern
     * Wenn die Tabelle nicht existiert wird diese erstellt
     *
     * @param string $table
     * @param array<string, mixed> $fields
     */
    public function createTableFields($table, $fields): void
    {
        $this->DB->table()->addColumn($table, $fields);
    }

    /**
     * Löscht die Felder einer Tabelle, wenn die Tabelle keine Felder mehr hätte wird diese gelöscht
     *
     * @param string $table - Tabelle
     * @param string[] $fields - Felder welche gelöscht werden sollen
     */
    public function deleteTableFields($table, $fields): void
    {
        $this->DB->table()->deleteFields($table, $fields);
    }

    /**
     * Prüft ob eine tabelle existiert
     *
     * @param string $table - Tabellenname welcher gesucht wird
     *
     * @return boolean
     */
    public function existTable($table): bool
    {
        return $this->DB->table()->exist($table);
    }

    /**
     * Löscht eine Tabelle
     *
     * @param string $table
     */
    public function deleteTable($table): void
    {
        $this->DB->table()->delete($table);
    }

    /**
     * Prüft ob eine Spalte in der Tabelle existiert
     *
     * @param string $table
     * @param string $row
     *
     * @return boolean
     */
    public function existRowInTable($table, $row): bool
    {
        return $this->DB->table()->existColumnInTable($table, $row);
    }

    /**
     * Alle Spalten der Tabelle bekommen
     *
     * @param string $table
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRowsFromTable($table)
    {
        return $this->DB->table()->getColumns($table);
    }

    /**
     * Löscht eine Spalte aus der Tabelle
     *
     * @param string $table
     * @param string $row
     */
    public function deleteRow($table, $row): void
    {
        $this->DB->table()->deleteColumn($table, $row);
    }

    /**
     * Liefert die Primary Keys einer Tabelle
     *
     * @param string $table
     *
     * @return array<int, array<string, mixed>>
     */
    public function getKeys($table)
    {
        return $this->DB->table()->getKeys($table);
    }

    /**
     * Prüft ob der PrimaryKey gesetzt ist
     *
     * @param string $table
     * @param string|array<string, mixed> $key
     *
     * @return boolean
     */
    public function issetPrimaryKey($table, $key): bool
    {
        return $this->DB->table()->issetPrimaryKey($table, $key);
    }

    /**
     * Setzt ein PrimaryKey einer Tabelle
     *
     * @param string $table
     * @param string|array<string, mixed> $key
     *
     * @return boolean
     */
    public function setPrimaryKey($table, $key): bool
    {
        return $this->DB->table()->setPrimaryKey($table, $key);
    }

    /**
     * Prüft ob ein Index gesetzt ist
     *
     * @param string $table
     * @param string|integer $key
     *
     * @return boolean
     */
    public function issetIndex($table, $key): bool
    {
        return $this->DB->table()->issetIndex($table, $key);
    }

    /**
     * Liefert die Indexes einer Tabelle
     *
     * @param string $table
     *
     * @return array<int, array<string, mixed>>
     */
    public function getIndex($table)
    {
        return $this->DB->table()->getIndex($table);
    }

    /**
     * Setzt einen Index
     *
     * @param string $table
     * @param string|array<string, mixed> $index
     *
     * @return boolean
     */
    public function setIndex($table, $index): bool
    {
        return $this->DB->table()->setIndex($table, $index);
    }

    /**
     * Setzt einen Index
     *
     * @param string $table
     * @param string|array<string, mixed> $index
     *
     * @return boolean
     */
    public function setFulltext($table, $index): bool
    {
        return $this->DB->table()->setFulltext($table, $index);
    }

    /**
     * Prüft ob ein Fulltext auf das Feld gesetzt ist
     *
     * @param string $table
     * @param string|integer $key
     *
     * @return boolean
     */
    public function issetFulltext($table, $key): bool
    {
        return $this->DB->table()->issetFulltext($table, $key);
    }

    /**
     * backup method - not implemented
     *
     * @param string $table
     * @param string $file
     *
     * @return void
     *
     * @deprecated
     */
    public function backup($table, $file)
    {
    }

    /**
     * restore method - not implemented
     *
     * @param string $file
     * @param string $table
     *
     * @return void
     *
     * @deprecated
     */
    public function restore($file, $table)
    {
    }
}
