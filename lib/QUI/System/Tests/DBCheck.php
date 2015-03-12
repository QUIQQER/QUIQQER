<?php

/**
 * This class contains \QUI\System\Tests\DBCheck
 */

namespace QUI\System\Tests;

use QUI;
use QUI\Utils\System\File as SystemFile;
use QUI\Utils\XML as XML;
use QUI\Utils\String as String;

/**
 * Database Check - Compares existing QUIQQER database tables with database.xml files
 * and detects discrepancies
 *
 * @package quiqqer/quiqqer
 * @author www.pcsg.de (Patrick Müller)
 */
class DBCheck extends QUI\System\Test
{
    protected $_Tables = null;
    protected $_error  = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setAttributes(array(
            'title'       => 'QUIQQER - Database Check',
            'description' => 'Compares existing QUIQQER database tables ' .
                             'with database.xml files and detects discrepancies.'
        ));

        $this->_isRequired = self::TEST_IS_REQUIRED;
    }

    /**
     * Database Check
     *
     * @return self::STATUS_OK|self::STATUS_ERROR
     */
    public function execute()
    {
        if ( defined( 'OPT_DIR' ) )
        {
            $packages_dir = OPT_DIR;
        } else
        {
            return self::STATUS_ERROR;
        }

        $packages = SystemFile::readDir( $packages_dir );

        $this->_Tables = QUI::getDataBase()->Table();

        // first we need all databases
        foreach ( $packages as $package )
        {
            if ( $package == 'composer' ) {
                continue;
            }

            $package_dir = $packages_dir . $package;
            $list        = SystemFile::readDir( $package_dir );

            foreach ( $list as $sub )
            {
                if ( !is_dir( $package_dir .'/'. $sub ) ) {
                    continue;
                }

                $databaseXml = $package_dir .'/'. $sub .'/database.xml';

                if ( !file_exists( $databaseXml ) ) {
                    continue;
                }

                try
                {
                    $this->_checkIntegrity( $databaseXml );

                } catch ( \Exception $Exception )
                {
                    QUI\System\Log::addWarning( $databaseXml );
                    QUI\System\Log::addWarning( $Exception->getMessage() );
                    QUI\System\Log::addWarning( $Exception->getTraceAsString() );
                }
            }
        }

        if ( $this->_error ) {
            return self::STATUS_ERROR;
        }

        return self::STATUS_OK;
    }

    /**
     * Main method for extracting table info from xml and compare it with the database
     *
     * @param $xmlFile
     */
    protected function _checkIntegrity($xmlFile)
    {
        $content = XML::getDataBaseFromXml( $xmlFile );

        // Project tables
        if ( isset( $content[ 'projects' ] ) )
        {
            $projects = QUI::getProjectManager()->getProjects( true );

            $langTables   = array(); // language dependant tables
            $noLangTables = array(); // language independant tables

            foreach ( $content[ 'projects' ] as $info )
            {
                $checkData = $this->_extractTableData( $info, $xmlFile );

                if ( !empty( $info[ 'no-project-lang' ] ) )
                {
                    $noLangTables[] = $checkData;
                } else
                {
                    $langTables[] = $checkData;
                }
            }

            // first check language independant project tables
            if ( !empty( $noLangTables ) )
            {
                foreach ( $projects as $Project )
                {
                    foreach ( $langTables as $tblData )
                    {
                        $projectTable = QUI::getDBProjectTableName(
                            $tblData[ 'table' ], $Project, false
                        );

                        $this->_checkTableIntegrity(
                            $projectTable,
                            $tblData,
                            $xmlFile
                        );
                    }
                }
            }

            // check language dependant project tables
            if ( !empty( $langTables ) )
            {
                foreach ( $projects as $Project )
                {
                    $langs = $Project->getAttribute( 'langs' );

                    foreach ( $langs as $lang )
                    {
                        foreach ( $langTables as $tblData )
                        {
                            $projectTable = QUI::getDBProjectTableName(
                                $tblData[ 'table' ], $Project, $lang
                            );

                            $this->_checkTableIntegrity(
                                $projectTable,
                                $tblData,
                                $xmlFile
                            );
                        }
                    }
                }
            }
        }

        if ( isset( $content[ 'globals' ] ) )
        {
            $globalTables = array();

            foreach ( $content[ 'globals' ] as $info ) {
                $globalTables[] = $this->_extractTableData( $info, $xmlFile );
            }

            foreach ( $globalTables as $tblData )
            {
                $table = QUI::getDBTableName( $tblData[ 'table' ] );
                $this->_checkTableIntegrity( $table, $tblData, $xmlFile );
            }
        }
    }

    /**
     * Extracts check relevant data from xml table information
     *
     * @param $info
     * @return array
     */
    protected function _extractTableData($info, $xmlFile)
    {
        $primaryKeys = array();
        $checkData   = array(
            'table'    => $info[ 'suffix' ],
            'fields'   => $info[ 'fields' ],
            'indices'  => false,
            'auto_inc' => false
        );

        // if primary keys are not explicitly declared by attribute
        // try to extract them out of the column structure declaration
        if ( isset( $info[ 'primary' ] ) )
        {
            $primaryKeys = $info[ 'primary' ];
        } else
        {
            foreach ( $info[ 'fields' ] as $column => $structure )
            {
                $structure = String::toLower( $structure );

                if ( mb_strpos( $structure, 'primary key' ) !== false ) {
                    $primaryKeys[] = $column;
                }
            }
        }

        $checkData[ 'primaryKeys' ] = $primaryKeys;

        if ( isset( $info[ 'index' ] ) ) {
            $checkData[ 'indices' ] = $info[ 'index' ];
        }

        if ( isset( $info[ 'auto_increment' ] ) )
        {
            $checkData[ 'auto_inc' ] = $info[ 'auto_increment' ];
        } else
        {
            foreach ( $info[ 'fields' ] as $column => $structure )
            {
                $structure = String::toLower( $structure );

                if ( mb_strpos( $structure, 'auto_increment' ) !== false )
                {
                    $checkData[ 'auto_inc' ] = $column;
                    break; // @todo sobald auto_inc auch für mehrere felder definiert werden kann, anpassen
                }
            }
        }

        // check if user can set individual primary keys
        if ( empty( $info[ 'no-site-reference' ] ) )
        {
            if ( empty( $info[ 'no-project-lang' ] ) &&
                !empty( $primaryKeys ) )
            {
                if ( !(count( $primaryKeys ) === 1 && in_array( 'id', $primaryKeys )) )
                {
                    QUI\System\Log::addWarning(
                        "Database Check Warning: " .
                        "Primary Key error -> " .
                        "XML file ($xmlFile) declares a primary key for table " . $checkData[ 'table' ] .
                        " You can only declare a primary key if the table has the " .
                        "-> no-site-reference=\"1\" <- attribute OR the " .
                        "-> no-project-lang=\"1\" <- attribute!"
                    );

                    $this->_error = true;
                }
            }

            // assume the xml file declares an id key
            // although technically it is created by the system in this special case
            if ( !in_array( 'id', $primaryKeys ) )
            {
                $checkData[ 'primaryKeys' ][] = 'id';
                $checkData[ 'fields' ][ 'id' ] = 'BIGINT(20) NOT NULL PRIMARY KEY';
            }
        }

        return $checkData;
    }

    /**
     * Compares xml data with database table data
     *
     * @param string $table - name of the table in the database
     * @param array $tblData - the data extracted form the database.xml
     * @param string $xmlFile
     */
    protected function _checkTableIntegrity($table, $tblData, $xmlFile)
    {
        // xml data
        $tbl         = $tblData[ 'table' ];
        $indices     = $tblData[ 'indices' ];
        $primaryKeys = $tblData[ 'primaryKeys' ];
        $autoInc     = $tblData[ 'auto_inc' ];
        $fields      = $tblData[ 'fields' ];

        if ( !$this->_Tables->exist( $table ) )
        {
            QUI\System\Log::addWarning(
                "Database Check Warning: " .
                "Missing table -> " .
                "Table \"$table\" is not found in the database." .
                " Please execute the QUIQQER Setup."
            );

            $this->_error = true;

            return;
        }

        // get table info from database
        $dbKeys   = $this->_Tables->getKeys( $table, true, true );
        $dbFields = $this->_Tables->getFields( $table );

        // compare primary keys
        $keyCompare = array_intersect( $primaryKeys, $dbKeys );

        if ( count( $primaryKeys ) !== count( $dbKeys ) ||
             count( $keyCompare ) !== count( $primaryKeys ) )
        {
            $_dbKeys = empty( $dbKeys ) ? '(none)' : implode( ',', $dbKeys );
            $_prKeys = empty( $primaryKeys ) ? '(none)' : implode( ',', $primaryKeys );

            QUI\System\Log::addWarning(
                "Database Check Warning: " .
                "Primary Key mismatch -> " .
                "Database primary keys ($table): " . $_dbKeys .
                " | XML primary keys ($xmlFile): " . $_prKeys
            );

            $this->_error = true;
        }

        // check if xml file declares fields that are not present in the database table
        $xmlFieldsDiff = array_diff(
            array_keys( $fields ),
            $dbFields
        );

        if ( !empty( $xmlFieldsDiff ) )
        {
            QUI\System\Log::addWarning(
                "Database Check Warning: " .
                "Table fields mismatch -> " .
                "The xml file ($xmlFile) declares table fields for $table that are " .
                "different from those currently in the database: " .
                implode( ',', $xmlFieldsDiff )
            );

            $this->_error = true;
        }

        // collect detailled column information from database
        $dbFieldsData = $this->_Tables->getFields( $table, false );

        foreach ( $dbFieldsData as $field )
        {
            $fieldName = $field[ 'Field' ];

            if ( !isset( $fields[ $fieldName ] ) ) {
                continue;
            }

            // column declaration from the xml file
            $fieldData = String::toLower( $fields[ $fieldName ] );

            /*** NULL/NOT NULL check ***/
            $nullable = true;

            if ( mb_strpos( $fieldData, 'not null' ) !== false ) {
                $nullable = false;
            }

            // check if column in datase is nullable
            $isNullable = true;

            if ( $field[ 'Null' ] === 'NO' ) {
                $isNullable = false;
            }

            if ( $nullable !== $isNullable )
            {
                $should = $nullable ? "NULL" : "NOT NULL";
                $is     = $isNullable ? "NULL" : "NOT NULL";

                QUI\System\Log::addWarning(
                    "Database Check Warning: " .
                    "Field structure mismatch -> " .
                    "The xml file ($xmlFile) for table $tbl says that field \"$fieldName\" " .
                    "should be $should but the database says it is $is" .
                    " in table $table."
                );

                $this->_error = true;
            }

            /*** AUTO_INCREMENT check ***/
            $autoIncrement = false;

            if ( $autoInc === $fieldName ) {
                $autoIncrement = true;
            }

            $isAutoIncrement = false;

            if ( $field[ 'Extra' ] === 'auto_increment' ) {
                $isAutoIncrement = true;
            }

            if ( $autoIncrement !== $isAutoIncrement )
            {
                $should = $autoIncrement ? "AUTO_INCREMENT" : "not AUTO_INCREMENT";
                $is     = $isAutoIncrement ? "AUTO_INCREMENT" : "not AUTO_INCREMENT";

                QUI\System\Log::addWarning(
                    "Database Check Warning: " .
                    "Field structure mismatch -> " .
                    "The xml file ($xmlFile) for table $tbl says that field \"$fieldName\" " .
                    "should be $should but the database says it is $is" .
                    " in table $table."
                );

                $this->_error = true;
            }

            /*** DATATYPE check ***/
            $isDatatype = $field[ 'Type' ];

            // clear xml field data of already checked content
            $fieldData = str_replace(
                array(
                    'primary key',
                    'not null',
                    'null',
                    'auto_increment'
                ),
                '',
                $fieldData
            );

            $fieldData = trim( $fieldData );

            // correct things like "varchar( 20 )" to "varchar(20)" to match the column information
            $fieldData = preg_replace( '#\(\D*(\d+)\D*\)#i', '($1)', $fieldData );

            if ( mb_strpos( $fieldData, $isDatatype ) === false )
            {
                QUI\System\Log::addWarning(
                    "Database Check Warning: " .
                    "Field structure mismatch -> " .
                    "The xml file ($xmlFile) for table $tbl says that field \"$fieldName\" " .
                    "should be \"$fieldData\"" .
                    " but the database says it is \"$isDatatype\" for table $table."
                );

                $this->_error = true;
            }
        }
    }
}
