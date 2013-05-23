<?php

/**
 * This file contains the Utils_DbTables
 */

/**
 * QUIQQER DataBase Layer for table operations
 *
 * @uses PDO
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils
 */

class Utils_DbTables
{
    /**
     * internal db object
     * @var Utils_Db
     */
    protected $_DB = null;

    /**
     * Konstruktor
     *
     * @param Utils_Db $DB
     */
    public function __construct(Utils_Db $DB)
    {
        $this->_DB = $DB;
    }

    /**
     * Returns all tables in the database
     *
     * @return Array
     */
    public function getTables()
    {
        $tables = array();

        if ( $this->_DB->isSQLite() )
        {
            $result = $this->_DB->getPDO()->query(
                "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name"
            )->fetchAll();

        } else
        {
            $result = $this->_DB->getPDO()->query("SHOW tables")->fetchAll();
        }

        foreach ( $result as $entry )
        {
            if ( isset( $entry[0] ) ) {
                $tables[] = $entry[0];
            }
        }

        return $tables;
    }

    /**
     * Optimiert Tabellen
     *
     * @param String || Array $tables
     */
    public function optimize($tables)
    {
        if ( is_string( $tables ) ) {
            $tables = array( $tables );
        }

        return $this->_DB->getPDO()->query(
            'OPTIMIZE TABLE `'. implode('`,`', $tables) .'`'
        )->fetchAll();
    }

    /**
     * Prüft ob eine Tabelle existiert
     *
     * @param String $table - Tabellenname welcher gesucht wird
     * @return Bool
     */
    public function exist($table)
    {
        if ( $this->_DB->isSQLite() )
        {
            $data = $this->_DB->getPDO()->query(
                "SELECT name FROM sqlite_master WHERE type='table' AND name ='". $table ."'"
            )->fetchAll();
        } else
        {
            $data = $this->_DB->getPDO()->query(
                'SHOW TABLES FROM `'. $this->_DB->getAttribute('dbname') .'` LIKE "'. $table .'"'
            )->fetchAll();
        }

        return count( $data ) > 0 ? true : false;
    }

    /**
     * Delete a table
     *
     * @param String $table
     */
    public function delete($table)
    {
        if ( !$this->exist( $table ) ) {
            return;
        }

        return $this->_DB->getPDO()->query(
            'DROP TABLE `'. $table .'`'
        );
    }

    /**
     * Execut a TRUNCATE on a table
     * empties a table completely
     *
     * @param String $table
     */
    public function truncate($table)
    {
        if ( !$this->exist( $table ) ) {
            return;
        }

        return $this->_DB->getPDO()->query(
            'TRUNCATE TABLE `'. $table .'`'
        );
    }

    /**
     * Erstellt eine Tabelle mit Feldern
     *
     * @param String $table
     * @param String $fields
     *
     * @return Bool - if table exists or not
     */
    public function create($table, $fields)
    {
        if ( !isset( $fields ) || !is_array( $fields ) )
        {
            throw new QExceptionDBError(
                'No Array given Utils_DbTables->createTable'
            );
        }

        $sql = 'CREATE TABLE `'. $this->_DB->getAttribute('dbname') .'`.`'. $table .'` (';

        if ( Utils_Array::isAssoc( $fields ) )
        {
            foreach ( $fields as $key => $type ) {
                $sql .= '`'.$key.'` '.$type.',';
            }

            $sql = substr( $sql, 0, -1 );
        } else
        {
            $len = count( $fields );

            for ( $i = 0; $i < $len; $i++ )
            {
                $sql .= $fields[$i];

                if ( $i < $len-1 ) {
                    $sql .= ',';
                }
            }
        }

        $sql .= ') ENGINE = MYISAM DEFAULT CHARSET = utf8;';

        $this->_DB->getPDO()->exec( $sql );

        return $this->exist( $table );
    }

    /**
     * Field Methods
     */

    /**
     * Tabellen Felder
     *
     * @param String $table
     * @return Array
     */
    public function getFields($table)
    {
        $PDO = $this->_DB->getPDO();

        if ( $this->_DB->isSQLite() )
        {
            $result = $PDO->query(
                "SELECT sql FROM sqlite_master
                WHERE tbl_name = '". $table ."' AND type = 'table'"
            )->fetchAll();

            if ( !isset( $result[0] ) && !isset( $result[0] ) ) {
                return array();
            }

            preg_match("/\((.*?)\)/", $result[0]['sql'], $matches);

            $fields = array();
            $expl    = explode( ',', $matches[1] );

            foreach ( $expl as $part ) {
                $fields[] = trim( $part, ' "' );
            }

            return $fields;
        }


        $Stmnt  = $PDO->query( "SHOW COLUMNS FROM `" . $table ."`" );
        $result = $Stmnt->fetchAll( \PDO::FETCH_ASSOC );
        $fields = array();

        foreach ( $result as $entry ) {
            $fields[] = $entry['Field'];
        }

        return $fields;
    }

    /**
     * Erweitert Tabellen mit den Feldern
     * Wenn die Tabelle nicht existiert wird diese erstellt
     *
     * @param String $table
     * @param Array $fields
     */
    public function appendFields($table, $fields)
    {
        if ( $this->exist( $table ) == false )
        {
            $this->create( $table, $fields );
            return;
        }

        $tbl_fields = $this->getFields( $table );

        foreach ( $fields as $field => $type )
        {
            if ( !in_array( $field, $tbl_fields ) )
            {
                $this->_DB->getPDO()->exec(
                    'ALTER TABLE `'. $table .'` ADD `'. $field .'` '. $type .';'
                );
            }
        }
    }

    /**
     * Löscht ein Feld / Spalte aus der Tabelle
     *
     * @param unknown_type $table
     * @param unknown_type $fields
     */
    public function deleteFields($table, $fields)
    {
        $table = \Utils_Security_Orthos::clearMySQL( $table );

        if ( $this->exist( $table ) == false) {
            return true;
        }

        $tbl_fields   = $this->getFields( $table );
        $table_fields = Utils_Array::toAssoc( $tbl_fields );

        // prüfen ob die Tabelle leer wäre wenn alle Felder gelöscht werden
        // wenn ja, Tabelle löschen
        foreach ( $fields as $field => $type )
        {
            if ( isset( $table_fields[ $field ] ) ) {
                unset( $table_fields[ $field ] );
            }
        }

        if ( empty( $table_fields ) )
        {
            $this->delete( $table );
            return;
        }


        // Einzeln die Felder löschen
        foreach ( $fields as $field => $type )
        {
            if ( in_array( $field, $tbl_fields ) ) {
                $this->deleteColum( $table, $field );
            }
        }
    }

    /**
     * Column Methods
     */

    /**
     * Prüft ob eine Spalte in der Tabelle existiert
     *
     * @param unknown_type $table
     * @param unknown_type $row
     *
     * @return Bool
     */
    public function existColumnInTable($table, $row)
    {
        if ( $this->_DB->isSQLite() == false )
        {
            $data = $this->_DB->getPDO()->query(
                'SHOW COLUMNS FROM `'. $table .'` LIKE "'. $row .'"'
            )->fetchAll();

            return count($data) > 0 ? true : false;
        }


        // sqlite part
        $columns = $this->getFields( $table );

        foreach ( $columns as $col )
        {
            if ( $col == $row ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Alle Spalten der Tabelle bekommen
     *
     * @param String $table
     * @return Array
     */
    public function getColumns($table)
    {
        if ( $this->_DB->isSQLite() ) {
            return $this->getFields( $table );
        }

        return $this->_DB->getPDO()->query(
            'SHOW COLUMNS FROM `'. $table .'`'
        )->fetchAll();
    }

    /**
     * Return the informations of a column
     *
     * @param String $table - Table name
     * @param String $column - Row name
     */
    public function getColumn($table, $column)
    {
        return $this->_DB->getPDO()->query(
            'SHOW COLUMNS FROM `'. $table .'` LIKE "'. $column .'"'
        )->fetch();
    }

    /**
     * Löscht eine Spalte aus der Tabelle
     *
     * @param unknown_type $table
     * @param unknown_type $row
     */
    public function deleteColumn($table, $row)
    {
        $table = Utils_Security_Orthos::clearMySQL($table);
        $row   = Utils_Security_Orthos::clearMySQL($row);

        if (!$this->existColumnInTable($table, $row)) {
            return;
        }

        $data = $this->_DB->getPDO()->query(
            'ALTER TABLE `'. $table .'` DROP `'. $row .'`'
        )->fetch();

        return $data ? true : false;
    }

    /**
     * Key Methods
     */

    /**
     * Schlüssel der Tabelle bekommen
     *
     * @param unknown_type $table
     * @return Array
     */
    public function getKeys($table)
    {
        return $this->_DB->getPDO()->query(
            'SHOW KEYS FROM `'. $table .'`'
        )->fetchAll();
    }

    /**
     * Prüft ob der PrimaryKey gesetzt ist
     *
     * @param String $table
     * @param String || Array $key
     *
     * @return Bool
     */
    public function issetPrimaryKey($table, $key)
    {
        if (is_array($key))
        {
            foreach ($key as $entry)
            {
                if ($this->_issetPrimaryKey($table, $entry) == false) {
                    return false;
                }
            }

            return true;
        }

        return $this->_issetPrimaryKey($table, $key);
    }

    /**
     * Helper for issetPrimaryKey
     * @see issetPrimaryKey
     *
     * @param String $table
     * @param String $key
     *
     * @return Bool
     */
    protected function _issetPrimaryKey($table, $key)
    {
        $keys = $this->getKeys($table);

        foreach ($keys as $entry)
        {
            if (isset($entry['Column_name']) &&
                $entry['Column_name'] == $key)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Setzt ein PrimaryKey einer Tabelle
     *
     * @param String $table
     * @param String|Array $key
     *
     * @return Bool
     */
    public function setPrimaryKey($table, $key)
    {
        if ($this->issetPrimaryKey($table, $key)) {
            return true;
        }

        $k = $key;

        if (is_array($key))
        {
            $k = "`". implode("`,`", $key) ."`";
        } else
        {
            $k = "`". $key ."`";
        }

        $this->_DB->getPDO()->exec(
            'ALTER TABLE `'. $table .'` ADD PRIMARY KEY('. $k .')'
        );

        return $this->issetPrimaryKey($table, $key);
    }

    /**
     * Index Methods
     */

    /**
     * Prüft ob ein Index gesetzt ist
     *
     * @param unknown_type $table
     * @param String | Integer $key
     *
     * @return Bool
     */
    public function issetIndex($table, $key)
    {
        if (is_array($key))
        {
            foreach ($key as $entry)
            {
                if ($this->_issetIndex($table, $entry) == false) {
                    return false;
                }
            }

            return true;
        }

        return $this->_issetIndex($table, $key);
    }

    /**
     * Prüft ob ein Index gesetzt ist -> subroutine
     *
     * @param String $table
     * @param String $key
     *
     * @return Bool
     */
    protected function _issetIndex($table, $key)
    {
        $i = $this->getIndex($table);

        foreach ($i as $entry)
        {
            if (isset($entry['Column_name']) &&
                $entry['Column_name'] == $key)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Liefert die Indexes einer Tabelle
     *
     * @param String $table
     * @return unknown
     */
    public function getIndex($table)
    {
        return $this->_DB->getPDO()->query(
            'SHOW INDEX FROM `'. $table .'`'
        )->fetchAll();
    }

    /**
     * Setzt einen Index
     *
     * @param String $table
     * @param String || Array $index
     *
     * @return Bool
     */
    public function setIndex($table, $index)
    {
        if ($this->issetIndex($table, $index)) {
            return true;
        }

        $in = $index;

        if (is_array($index))
        {
            $in = "`". implode("`,`", $index) ."`";
        } else
        {
             $in = "`". $index ."`";
        }

        $result = $this->_DB->getPDO()->exec(
            'ALTER TABLE `'. $table .'` ADD INDEX('. $in .')'
        );

        return $this->issetIndex($table, $index);
    }

    /**
     * Set the Autoincrement to the column
     *
     * @param String $table
     * @param String $index
     */
    public function setAutoIncrement($table, $index)
    {
        $column = $this->getColumn($table, $index);

        $query  = 'ALTER TABLE `'. $table .'`';
        $query .= 'MODIFY COLUMN `'. $index .'`';

        $query .= ' '. $column['Type'];

        if ($column['Null'] === 'No')
        {
            $query .= ' NOT NULL';
        } else
        {
            $query .= ' NULL';
        }

        $query .= ' AUTO_INCREMENT';

        $this->_DB->getPDO()->exec($query);
    }

    /**
     * Fulltext Methods
     */

    /**
     * Setzt einen Fulltext
     *
     * @param String $table
     * @param String || Array $index
     *
     * @return Bool
     */
    public function setFulltext($table, $index)
    {
        if ($this->issetFulltext($table, $index)) {
            return true;
        }

        $in = $index;

        if (is_array($index))
        {
            $in = "`". implode("`,`", $index) ."`";
        } else
        {
             $in = "`". $index ."`";
        }

        $this->_DB->getPDO()->exec(
            'ALTER TABLE `'. $table .'` ADD FULLTEXT('. $in .')'
        );

        return $this->issetFulltext($table, $index);
    }


    /**
     * Prüft ob ein Fulltext auf das Feld gesetzt ist
     *
     * @param String $table
     * @param String|Integer $key
     *
     * @return Bool
     */
    public function issetFulltext($table, $key)
    {
        if (is_array($key))
        {
            foreach ($key as $entry)
            {
                if ($this->_issetFulltext($table, $entry) == false) {
                    return false;
                }
            }

            return true;
        }

        return $this->_issetFulltext($table, $key);
    }

    /**
     * Prüft ob ein Fulltext auf das Feld gesetzt ist -> subroutine
     *
     * @param String $table
     * @param String $key
     */
    protected function _issetFulltext($table, $key)
    {
        $keys = $this->getKeys($table);

        foreach ($keys as $entry)
        {
            if (isset($entry['Column_name']) &&
                isset($entry['Index_type']) &&
                $entry['Column_name'] == $key &&
                $entry['Index_type'] == 'FULLTEXT')
            {
                return true;
            }
        }

        return false;
    }
}

?>