<?php

/**
 * This class contains \QUI\System\Tests\DBCheck
 */

namespace QUI\System\Tests;

use Exception;
use QUI;
use QUI\Database\Tables;
use QUI\Projects\Project;
use QUI\Utils\StringHelper as StringHelper;
use QUI\Utils\System\File as SystemFile;
use QUI\Utils\Text\XML;

use function array_diff;
use function array_intersect;
use function array_keys;
use function count;
use function defined;
use function explode;
use function file_exists;
use function implode;
use function in_array;
use function is_dir;
use function mb_strpos;
use function preg_replace;
use function str_replace;
use function trim;

/**
 * Database Check - Compares existing QUIQQER database tables with database.xml files
 * and detects discrepancies
 *
 * @author  www.pcsg.de (Patrick Müller)
 * @licence For copyright and license information, please view the /README.md
 */
class DBCheck extends QUI\System\Test
{
    /**
     * @var \QUI\Database\Tables
     */
    protected $Tables = null;

    /**
     * @var bool
     */
    protected $error = false;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setAttributes([
            'title' => 'QUIQQER - Database Check (on failure check error log!)',
            'description' => 'Compares existing QUIQQER database tables ' .
                'with database.xml files and detects discrepancies.'
        ]);

        $this->isRequired = self::TEST_IS_REQUIRED;
    }

    /**
     * Database Check
     *
     * @return integer
     */
    public function execute()
    {
        if (defined('OPT_DIR')) {
            $packages_dir = OPT_DIR;
        } else {
            return self::STATUS_ERROR;
        }

        $packages = SystemFile::readDir($packages_dir);
        $this->Tables = QUI::getDataBase()->table();

        // first we need all databases
        foreach ($packages as $package) {
            if ($package == 'composer') {
                continue;
            }

            $package_dir = $packages_dir . $package;
            $list = SystemFile::readDir($package_dir);

            foreach ($list as $sub) {
                if (!is_dir($package_dir . '/' . $sub)) {
                    continue;
                }

                $databaseXml = $package_dir . '/' . $sub . '/database.xml';

                if (!file_exists($databaseXml)) {
                    continue;
                }

                try {
                    $this->checkIntegrity($databaseXml);
                    $this->outputError($databaseXml);
                } catch (Exception $Exception) {
                    QUI\System\Log::addWarning($databaseXml);
                    QUI\System\Log::addWarning($Exception->getMessage());
                    QUI\System\Log::addWarning($Exception->getTraceAsString());
                }
            }
        }

        if ($this->error) {
            return self::STATUS_ERROR;
        }

        return self::STATUS_OK;
    }

    /**
     * Main method for extracting table info from xml and compare it with the database
     *
     * @param $xmlFile
     */
    protected function checkIntegrity($xmlFile)
    {
        $content = XML::getDataBaseFromXml($xmlFile);

        // Project tables
        if (isset($content['projects'])) {
            $projects = QUI::getProjectManager()->getProjects(true);

            $langTables = []; // language dependant tables
            $noLangTables = []; // language independent tables

            foreach ($content['projects'] as $info) {
                $checkData = $this->extractTableData($info);

                if (!empty($info['no-project-lang'])) {
                    $noLangTables[] = $checkData;
                } else {
                    $langTables[] = $checkData;
                }
            }

            // first check language independent project tables
            if (!empty($noLangTables)) {
                foreach ($projects as $Project) {
                    foreach ($langTables as $tblData) {
                        $projectTable = QUI::getDBProjectTableName(
                            $tblData['table'],
                            $Project,
                            false
                        );

                        $this->checkTableIntegrity(
                            $projectTable,
                            $tblData
                        );
                    }
                }
            }

            // check language dependent project tables
            if (!empty($langTables)) {
                foreach ($projects as $Project) {
                    /* @var $Project \QUI\Projects\Project */
                    $langs = $Project->getAttribute('langs');

                    foreach ($langs as $lang) {
                        foreach ($langTables as $tblData) {
                            $projectTable = QUI::getDBProjectTableName(
                                $tblData['table'],
                                $Project,
                                $lang
                            );

                            $this->checkTableIntegrity(
                                $projectTable,
                                $tblData
                            );
                        }
                    }
                }
            }
        }

        if (isset($content['globals'])) {
            $globalTables = [];

            foreach ($content['globals'] as $info) {
                $globalTables[] = $this->extractTableData($info, true);
            }

            foreach ($globalTables as $tblData) {
                $table = QUI::getDBTableName($tblData['table']);
                $this->checkTableIntegrity($table, $tblData);
            }
        }
    }

    /**
     * Extracts check relevant data from xml table information
     *
     * @param      $info
     * @param boolean $isGlobal (optional) - is a global table
     *
     * @return array
     */
    protected function extractTableData($info, $isGlobal = false)
    {
        $primaryKeys = [];
        $checkData = [
            'table' => $info['suffix'],
            'fields' => $info['fields'],
            'indices' => false,
            'auto_inc' => false
        ];

        // if primary keys are not explicitly declared by attribute
        // try to extract them out of the column structure declaration
        if (isset($info['primary'])) {
            $primaryKeys = $info['primary'];
        } else {
            foreach ($info['fields'] as $column => $structure) {
                $structure = StringHelper::toLower($structure);

                if (mb_strpos($structure, 'primary key') !== false) {
                    $primaryKeys[] = $column;
                }
            }
        }

        $checkData['primaryKeys'] = $primaryKeys;

        if (isset($info['index'])) {
            $checkData['indices'] = $info['index'];
        }

        if (isset($info['auto_increment'])) {
            $checkData['auto_inc'] = $info['auto_increment'];
        } else {
            foreach ($info['fields'] as $column => $structure) {
                $structure = StringHelper::toLower($structure);

                if (mb_strpos($structure, 'auto_increment') !== false) {
                    $checkData['auto_inc'] = $column;
                    break; // @todo sobald auto_inc auch für mehrere felder definiert werden kann, anpassen
                }
            }
        }

        // check if user can set individual primary keys
        if (!$isGlobal && empty($info['no-site-reference'])) {
            if (empty($info['no-project-lang']) && !empty($primaryKeys)) {
                if (!(count($primaryKeys) === 1 && in_array('id', $primaryKeys))) {
                    $this->addError(
                        $checkData['table'],
                        "---",
                        "Primary Key error -> " .
                        "XML file declares a primary key for table "
                        . $checkData['table'] .
                        " You can only declare a primary key if the table has the "
                        .
                        "-> no-site-reference=\"1\" <- attribute OR the " .
                        "-> no-project-lang=\"1\" <- attribute!"
                    );
                }
            }

            // assume the xml file declares an id key
            // although, technically it is created by the system in this special case
            if (!in_array('id', $primaryKeys)) {
                $checkData['primaryKeys'][] = 'id';
                $checkData['fields']['id'] = 'BIGINT(20) NOT NULL PRIMARY KEY';
            }
        }

        return $checkData;
    }

    /**
     * @param string $table
     * @param string $dbTable
     * @param string $error
     */
    protected function addError($table, $dbTable, $error)
    {
        $this->errors[] = [
            'table' => $table,
            'dbTable' => $dbTable,
            'error' => $error
        ];

        $this->error = true;
    }

    /**
     * Compares xml data with database table data
     *
     * @param string $table - name of the table in the database
     * @param array $tblData - the data extracted form the database.xml
     */
    protected function checkTableIntegrity($table, $tblData)
    {
        // xml data
        $tbl = $tblData['table'];
        $primaryKeys = $tblData['primaryKeys'];
        $indices = $tblData['indices'];
        $autoInc = $tblData['auto_inc'];
        $fields = $tblData['fields'];

        if (!$this->Tables->exist($table)) {
            $this->addError(
                $tbl,
                $table,
                "Missing table -> " .
                "Table \"$table\" is not found in the database." .
                " Please execute the QUIQQER Setup."
            );

            return;
        }

        if (empty($primaryKeys) && empty($indices)) {
            $this->addError(
                $tbl,
                $table,
                "No PRIMARY KEY AND NO INDICES-> " .
                "Table \"$table\" has no PRIMARY KEY."
            );
        }

        // get table info from database
        $dbKeys = $this->Tables->getKeys($table);

        // get all primary keys
        foreach ($dbKeys as $k => $columnInfo) {
            if ($columnInfo['Key_name'] !== 'PRIMARY') {
                unset($dbKeys[$k]);
                continue;
            }

            $dbKeys[$k] = $columnInfo['Column_name'];
        }

        $_dbFields = $this->Tables->getFieldsInfos($table);
        $dbFields = [];

        foreach ($_dbFields as $_entry) {
            $dbFields[] = $_entry['Field'];
        }

        // compare primary keys
        $keyCompare = array_intersect($primaryKeys, $dbKeys);

        if (
            count($primaryKeys) !== count($dbKeys)
            || count($keyCompare) !== count($primaryKeys)
        ) {
            $_dbKeys = empty($dbKeys) ? '(none)' : implode(',', $dbKeys);
            $_prKeys = empty($primaryKeys) ? '(none)' : implode(',', $primaryKeys);

            $this->addError(
                $tbl,
                $table,
                "Primary Key mismatch -> " .
                "XML primary keys: " . $_prKeys .
                " | Database table primary keys: " . $_dbKeys
            );
        }


        // check if xml file declares fields that are not present in the database table
        $xmlFieldsDiff = array_diff(
            array_keys($fields),
            $dbFields
        );

        if (!empty($xmlFieldsDiff)) {
            $this->addError(
                $tbl,
                $table,
                "Missing table fields -> " .
                "The XML file declares table fields that are " .
                "missing in the database table: " .
                implode(',', $xmlFieldsDiff)
            );
        }

        // collect detailed column information from database
        $dbFieldsData = $this->Tables->getFieldsInfos($table);

        foreach ($dbFieldsData as $field) {
            $fieldName = $field['Field'];

            if (!isset($fields[$fieldName])) {
                continue;
            }

            // column declaration from the xml file
            $fieldData = StringHelper::toLower($fields[$fieldName]);

            /*** NULL/NOT NULL check ***/
            $nullable = true;

            if (mb_strpos($fieldData, 'not null') !== false) {
                $nullable = false;
            }

            // check if column in database is nullable
            $isNullable = true;

            if ($field['Null'] === 'NO') {
                $isNullable = false;
            }

            if ($nullable !== $isNullable) {
                $should = $nullable ? "NULL" : "NOT NULL";
                $is = $isNullable ? "NULL" : "NOT NULL";

                $this->addError(
                    $tbl,
                    $table,
                    "Field structure mismatch -> " .
                    "The XML file says that field \"$fieldName\" " .
                    "should be $should but the database says it is $is."
                );
            }

            /*** AUTO_INCREMENT check ***/
            $autoIncrement = false;

            if ($autoInc === $fieldName) {
                $autoIncrement = true;
            }

            $isAutoIncrement = false;

            if ($field['Extra'] === 'auto_increment') {
                $isAutoIncrement = true;
            }

            if ($autoIncrement !== $isAutoIncrement) {
                $should = $autoIncrement ? "AUTO_INCREMENT" : "not AUTO_INCREMENT";
                $is = $isAutoIncrement ? "AUTO_INCREMENT" : "not AUTO_INCREMENT";

                $this->addError(
                    $tbl,
                    $table,
                    "Field structure mismatch -> " .
                    "The XML file says that field \"$fieldName\" " .
                    "should be $should but the database says it is $is."
                );
            }

            /*** DATATYPE check ***/
            $isDatatype = $field['Type'];
            $notNeeded = [
                'primary key',
                'not null',
                'null',
                'auto_increment',
                'unsigned'
            ];

            // clear xml field data of already checked content
            $fieldData = str_replace($notNeeded, '', $fieldData);
            $isDatatype = str_replace($notNeeded, '', $isDatatype);

            if (str_contains($fieldData, 'default')) {
                $fieldData = explode('default', $fieldData);
                $fieldData = $fieldData[0];
            }

            $fieldData = trim($fieldData);
            $isDatatype = trim($isDatatype);

            // correct things like "varchar( 20 )" to "varchar(20)" to match the column information
            $fieldData = preg_replace('#\(\D*(\d+)\D*\)#i', '($1)', $fieldData);

            if (
                $fieldData === 'int'
                || $fieldData === 'mediumint'
                || $fieldData === 'smallint'
            ) {
                // int(10) = int
                if (str_contains($isDatatype, $fieldData . '(')) {
                    continue;
                }
            }

            if ($fieldData === 'boolean' && $isDatatype === 'tinyint(1)') {
                continue;
            }

            if (mb_strpos($fieldData, $isDatatype) === false) {
                $this->addError(
                    $tbl,
                    $table,
                    "Field structure mismatch -> " .
                    "The XML file says that field \"$fieldName\" " .
                    "should be \"$fieldData\"" .
                    " but the database says it is \"$isDatatype\"."
                );
            }
        }
    }

    /**
     * @param string $xmlFile
     */
    protected function outputError($xmlFile)
    {
        if (empty($this->errors)) {
            return;
        }

        $msg = "\n-> Database Check Errors in: $xmlFile <-";

        foreach ($this->errors as $k => $err) {
            $msg .= "\n";
            $msg .= "\n Error #" . ($k + 1) . ":";
            $msg .= "\n -------------------------";
            $msg .= "\n Definition for table: \t\"" . $err['table'] . "\"";
            $msg .= "\n Database table: \t\"" . $err['dbTable'] . "\"";
            $msg .= "\n Error: " . $err['error'];
        }

        QUI\System\Log::addWarning($msg);
        $this->errors = [];
    }
}
