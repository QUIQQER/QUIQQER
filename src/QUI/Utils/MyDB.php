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
        $this->DB = QUI::getDataBase();
    }

    /**
     * \QUI\Database\DB Objekt (Neues Datenbank Objekt)
     */
    public function getUtilsDB(): \QUI\Database\DB
    {
        return QUI::getDataBase();
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
        return QUI::getDataBase()->getPDO();
    }

    /**
     * MASKIERTE QUERY
     *
     * @param string $query
     *
     * @return array
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

        return $this->getPDO()->query($query)->fetchAll();
    }

    /**
     * MySQL Select
     *
     * @param array $params
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
     * @return array
     */
    public function select(array $params, $type = "ARRAY", $type2 = 'ARRAY')
    {
        return $this->getData($params, $type, $type2);
    }

    /**
     * Liefert Daten aus der Datenbank im Typ ARRAY oder ROW oder OBJEKT
     *
     * @param array $params
     * @param string $type = BOTH, NUM, ASSOC, OBJ
     * @param string $qtype = BOTH, NUM, ASSOC
     *
     * @return object|array
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
     * @param array $params
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
     * @return array
     */
    public function getFields($table)
    {
        return $this->DB->table()->getColumns($table);
    }

    /**
     * Gibt die Tabellen zurück
     *
     * @return array
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
     * @param string|array $fieldAndId
     *
     * @return array
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
     * @param array $fieldValue
     * @param string|array $fieldAndId
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
     * Insert
     *
     * @param string $table
     * @param array $fieldValue
     * @return string
     */
    public function insertData($table, $fieldValue)
    {
        return $this->addData($table, $fieldValue);
    }

    /**
     * add a data row
     *
     * @param string $table
     * @param array $FieldValue - [array('field1'=>'value1', 'field2'=>'value2', 'field3'=>'value3')]
     *
     * @return integer
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
     * @param array $params
     *
     * @return integer
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
     * @param string|array $fieldAndId
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
     * @param string|array $tables
     */
    public function optimize($tables): void
    {
        $this->DB->table()->optimize($tables);
    }

    /**
     * Enter description here...
     *
     * @param string $table
     * @param array $fields
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
     * @param array $fields
     */
    public function createTableFields($table, $fields): void
    {
        $this->DB->table()->addColumn($table, $fields);
    }

    /**
     * Löscht die Felder einer Tabelle, wenn die Tabelle keine Felder mehr hätte wird diese gelöscht
     *
     * @param string $table - Tabelle
     * @param array $fields - Felder welche gelöscht werden sollen
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
    public function existTable($table)
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
    public function existRowInTable($table, $row)
    {
        return $this->DB->table()->existColumnInTable($table, $row);
    }

    /**
     * Alle Spalten der Tabelle bekommen
     *
     * @param string $table
     *
     * @return array
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
     * @return array
     */
    public function getKeys($table)
    {
        return $this->DB->table()->getKeys($table);
    }

    /**
     * Prüft ob der PrimaryKey gesetzt ist
     *
     * @param string $table
     * @param string|array $key
     *
     * @return boolean
     */
    public function issetPrimaryKey($table, $key)
    {
        return $this->DB->table()->issetPrimaryKey($table, $key);
    }

    /**
     * Setzt ein PrimaryKey einer Tabelle
     *
     * @param string $table
     * @param string|array $key
     *
     * @return boolean
     */
    public function setPrimaryKey($table, $key)
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
    public function issetIndex($table, $key)
    {
        return $this->DB->table()->issetIndex($table, $key);
    }

    /**
     * Liefert die Indexes einer Tabelle
     *
     * @param string $table
     *
     * @return array
     */
    public function getIndex($table)
    {
        return $this->DB->table()->getIndex($table);
    }

    /**
     * Setzt einen Index
     *
     * @param string $table
     * @param string|array $index
     *
     * @return boolean
     */
    public function setIndex($table, $index)
    {
        return $this->DB->table()->setIndex($table, $index);
    }

    /**
     * Setzt einen Index
     *
     * @param string $table
     * @param string|array $index
     *
     * @return boolean
     */
    public function setFulltext($table, $index)
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
    public function issetFulltext($table, $key)
    {
        return $this->DB->table()->issetFulltext($table, $key);
    }

    /**
     * backup method - not implemented
     *
     * @param string $table
     * @param string $file
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
     * @deprecated
     */
    public function restore($file, $table)
    {
    }
}
