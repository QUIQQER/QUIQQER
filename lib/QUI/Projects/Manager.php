<?php

/**
 * This file contains the \QUI\Projects\Manager
 */

namespace QUI\Projects;

use QUI;
use QUI\Permissions\Permission;
use QUI\Utils\Security\Orthos;
use QUI\Utils\DOM;
use QUI\Utils\Text\XML;

/**
 * The Project Manager
 * The main object to get a project
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 *
 * @event onProjectConfigSave [ string project, Array config ]
 * @event onCreateProject [ string \QUI\Projects\Project ]
 *
 * @errorcodes 8xx Project Errors -> Look at Project.php
 */
class Manager
{
    /**
     * Projects config
     *
     * @var \QUI\Config
     */
    public static $Config = null;

    /**
     * laoded projects
     *
     * @var array
     */
    public static $projects = array();

    /**
     * standard project
     *
     * @var \QUI\Projects\Project
     */
    public static $Standard = null;

    /**
     * projects.ini
     *
     * @return \QUI\Config
     *
     * @throws QUI\Exception
     */
    public static function getConfig()
    {
        return QUI::getConfig('etc/projects.ini');
    }

    /**
     * set configuration for a project
     *
     * @param string $project
     * @param array $params
     *
     * @throws QUI\Exception
     * @throws \Exception
     */
    public static function setConfigForProject($project, $params = array())
    {
        $Project = $project;

        if (is_string($Project)
            || get_class($Project) != Project::class
        ) {
            $Project = self::getProject($project);
        }

        $projectName = $Project->getName();

        Permission::checkProjectPermission(
            'quiqqer.projects.setconfig',
            $Project
        );

        if (!is_array($params)) {
            $params = array();
        }

        $Config   = self::getConfig();
        $projects = $Config->toArray();

        // $config
        $availableConfig = self::getProjectConfigList($Project);
        $projectConfig   = array();

        if (isset($projects[$projectName])) {
            $projectConfig = $projects[$projectName];
        }

        // merge current config and available config
        foreach ($availableConfig as $key => $value) {
            if (!isset($projectConfig[$key])) {
                continue;
            }

            $str = Orthos::removeHTML($projectConfig[$key]);
            $str = Orthos::clearPath($str);

            $availableConfig[$key] = $str;
        }

        // merge params config with available / current config
        if (!empty($params)) {
            foreach ($availableConfig as $key => $value) {
                if (!isset($params[$key])) {
                    continue;
                }

                $str = Orthos::removeHTML($params[$key]);
                $str = Orthos::clearPath($str);

                $availableConfig[$key] = $str;
            }
        }

        // doppelte sprachen filtern
        $languages = explode(',', $availableConfig['langs']);
        $languages = array_unique($languages);

        $availableConfig['langs'] = implode(',', $languages);

        $Config->setSection($projectName, $availableConfig);
        $Config->save();


        QUI::getEvents()->fireEvent('projectConfigSave', array(
            $projectName,
            $availableConfig,
            $params
        ));

        // remove the project from the temp
        if (self::$projects[$projectName]) {
            unset(self::$projects[$projectName]);
        }

        // execute the project setup
        $Project = self::getProject($projectName);
        $Project->setup();

        /**
         * clear media cache
         * eq: if watermark settings changed
         *
         * @param array $config
         * @param array $oldConfig
         * @param Project $Project
         */
        $clearMediaCache = function ($config, $oldConfig, Project $Project) {
            if (!isset($config["media_watermark"])
                && !isset($config["media_watermark_position"])
                && !isset($config["media_image_library"])
            ) {
                return;
            }

            if (isset($config["media_watermark"])
                && isset($oldConfig['media_watermark'])
                && $config["media_watermark"] != $oldConfig['media_watermark']
            ) {
                // clear cache
                $Project->getMedia()->clearCache();

                return;
            }

            if (isset($config["media_watermark_ratio"])
                && isset($oldConfig['media_watermark_ratio'])
                && $config["media_watermark_ratio"] != $oldConfig['media_watermark_ratio']
            ) {
                // clear cache
                $Project->getMedia()->clearCache();

                return;
            }

            if (isset($config["media_watermark_position"])
                && isset($oldConfig['media_watermark_position'])
                && $config["media_watermark_position"] != $oldConfig['media_watermark_position']
            ) {
                // clear cache
                $Project->getMedia()->clearCache();

                return;
            }

            if (isset($config["media_image_library"])
                && isset($oldConfig['media_image_library'])
                && $config["media_image_library"] != $oldConfig['media_image_library']
            ) {
                // clear cache
                $Project->getMedia()->clearCache();

                return;
            }
        };

        $clearMediaCache($availableConfig, $projectConfig, $Project);

        // if this project should be the standard,
        // all other projects are not
        if (!isset($availableConfig['standard']) || $availableConfig['standard'] != 1) {
            return;
        }

        $projects = $Config->toArray();

        foreach ($projects as $_project => $settings) {
            if ($_project != $projectName) {
                $Config->setValue($_project, 'standard', 0);
            }
        }

        $Config->save();
    }

    /**
     * Return the config list
     *
     * @param \QUI\Projects\Project $Project
     *
     * @return array
     */
    public static function getProjectConfigList(QUI\Projects\Project $Project)
    {
        $cache = 'qui/projects/'.$Project->getName().'/configList';

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Exception $Exception) {
        }

        $config = array(
            "default_lang"             => "de",
            "langs"                    => "de",
            "admin_mail"               => "support@pcsg.de",
            "template"                 => "",
            "layout"                   => "",
            "image_text"               => "0",
            "standard"                 => "1",
            "adminSitemapMax"          => 20,
            "media_watermark"          => "",
            "media_watermark_position" => "",
            "media_watermark_ratio"    => "",
            "media_image_library"      => "",
            "media_maxUploadSize"      => "",
            "media_maxUploadFileSize"  => "",
            "media_createCacheOnSave"  => "1",
            "placeholder"              => "",
            "logo"                     => "",
            "favicon"                  => ""
        );

        // settings.xml
        $settingsXml = self::getRelatedSettingsXML($Project);

        foreach ($settingsXml as $file) {
            $Dom  = XML::getDomFromXml($file);
            $Path = new \DOMXPath($Dom);

            $settingsList = $Path->query("//project/settings");

            for ($i = 0, $len = $settingsList->length; $i < $len; $i++) {
                /* @var $Settings \DOMElement */
                $Settings = $settingsList->item($i);
                $sections = DOM::getConfigParamsFromDOM($Settings);

                $settingsName = $Settings->getAttribute('name');

                if (!empty($settingsName)) {
                    $settingsName = $settingsName.'.';
                }

                foreach ($sections as $section => $entry) {
                    foreach ($entry as $key => $param) {
                        $config[$settingsName.$section.'.'.$key] = '';

                        if (isset($param['default'])) {
                            $config[$settingsName.$section.'.'.$key] = $param['default'];
                        }
                    }
                }
            }
        }

        QUI\Cache\Manager::set($cache, $config);

        return $config;
    }

    /**
     * Return the projects count
     *
     * @return integer
     *
     * @throws QUI\Exception
     */
    public static function count()
    {
        $Config = self::getConfig();
        $config = $Config->toArray();

        return count($config);
    }

    /**
     * Decode project data
     * Decode a project json string to a Project or decode a project array to a Project
     *
     * @param string|array $project - project data
     *
     * @return \QUI\Projects\Project
     * @throws \QUI\Exception
     */
    public static function decode($project)
    {
        if (is_string($project)) {
            $project = json_decode($project, true);
        }

        if (!isset($project['name']) || !$project['name']) {
            throw new QUI\Exception(
                'Could not decode project data'
            );
        }

        $projectName = $project['name'];
        $projectLang = false;
        $projectTpl  = false;

        if (isset($project['lang'])) {
            $projectLang = $project['lang'];
        }

        if (isset($project['template'])) {
            $projectTpl = $project['template'];
        }

        return self::getProject($projectName, $projectLang, $projectTpl);
    }

    /**
     * Returns the current project
     *
     * @return Project
     * @throws \QUI\Exception
     */
    public static function get()
    {
        $Rewrite = QUI::getRewrite();

        if ($Rewrite->getParam('project')) {
            return self::getProject(
                $Rewrite->getParam('project'),
                $Rewrite->getParam('lang'),
                $Rewrite->getParam('template')
            );
        }

        $Standard = self::getStandard();

        // Falls andere Sprache gewünscht
        if ($Rewrite->getParam('lang')
            && $Rewrite->getParam('lang') != $Standard->getAttribute('lang')
        ) {
            return self::getProject(
                $Standard->getAttribute('name'),
                $Rewrite->getParam('lang')
            );
        }

        return $Standard;
    }

    /**
     * Returns a project
     *
     * @param string $project - Project name
     * @param string|boolean $lang - Project lang, optional (if not set, the standard language used)
     * @param string|boolean $template - used template, optional (if not set, the standard templaed used)
     *
     * @return \QUI\Projects\Project
     *
     * @throws QUI\Exception
     */
    public static function getProject($project, $lang = false, $template = false)
    {
        if ($lang == false && isset(self::$projects[$project])
            && isset(self::$projects[$project]['_standard'])
        ) {
            return self::$projects[$project]['_standard'];
        }

        if (isset(self::$projects[$project])
            && isset(self::$projects[$project][$lang])
        ) {
            return self::$projects[$project][$lang];
        }

        // Wenn der RAM zu voll wird, Objekte mal leeren
        if (QUI\Utils\System::memUsageToHigh()) {
            self::$projects = array();
        }


        if ($lang === false) {
            self::$projects[$project]['_standard'] = new QUI\Projects\Project($project);

            return self::$projects[$project]['_standard'];
        }

        self::$projects[$project][$lang] = new QUI\Projects\Project(
            $project,
            $lang,
            $template
        );

        return self::$projects[$project][$lang];
    }

    /**
     * Gibt alle Projektnamen zurück
     *
     * @param boolean $asobject - Als Objekte bekommen, default = false
     *
     * @return array
     *
     * @throws QUI\Exception
     */
    public static function getProjects($asobject = false)
    {
        $config = self::getConfig()->toArray();
        $list   = array();

        foreach ($config as $project => $conf) {
            try {
                $Project = self::getProject(
                    $project,
                    $conf['default_lang'],
                    $conf['template']
                );

                if (isset($conf['standard']) && $conf['standard'] == 1) {
                    self::$Standard = $Project;
                }

                if ($asobject == true) {
                    $list[] = $Project;
                } else {
                    $list[] = $project;
                }
            } catch (QUI\Exception $Exception) {
            }
        }

        return $list;
    }

    /**
     * Return all projects as objects
     *
     * @return array
     *
     * @throws QUI\Exception
     */
    public static function getProjectList()
    {
        $config = self::getConfig()->toArray();
        $result = array();

        foreach ($config as $project => $conf) {
            $langs = explode(',', trim($conf['langs']));

            foreach ($langs as $lang) {
                if (isset(self::$projects[$project])
                    && isset(self::$projects[$project][$lang])
                ) {
                    $result[] = self::$projects[$project][$lang];
                    continue;
                }

                try {
                    $result[] = self::getProject(
                        $project,
                        $lang,
                        $conf['template']
                    );
                } catch (QUI\Exception $Exception) {
                }
            }
        }

        return $result;
    }

    /**
     * Standard Projekt bekommen
     *
     * @return \QUI\Projects\Project
     * @throws QUI\Exception
     */
    public static function getStandard()
    {
        if (self::$Standard !== null) {
            return self::$Standard;
        }

        $config = self::getConfig()->toArray();

        if (!count($config)) {
            throw new QUI\Exception(
                'No project exist'
            );
        }

        foreach ($config as $project => $conf) {
            if (isset($conf['standard']) && $conf['standard'] == 1) {
                self::$Standard = self::getProject(
                    $project,
                    $conf['default_lang'],
                    $conf['template']
                );
            }
        }

        if (self::$Standard === null) {
            QUI\System\Log::addAlert(
                'No standard project are set. Please define a standard projekt'
            );

            $project = key($config);

            self::$Standard = QUI\Projects\Manager::getProject(
                $project,
                $config[key($config)]['default_lang']
            );
        }

        return self::$Standard;
    }

    /**
     * Create a new project
     *
     * @param string $name - Project name
     * @param string $lang - Project lang
     *
     * @return \QUI\Projects\Project
     * @throws \QUI\Exception
     * @throws \Exception
     *
     * @todo noch einmal anschauen und übersichtlicher schreiben
     */
    public static function createProject($name, $lang)
    {
        Permission::checkPermission(
            'quiqqer.projects.create'
        );

        if (strlen($name) <= 2) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.project.longer.two.signs'
                ),
                801
            );
        }

        if (strlen($lang) != 2) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.project.lang.not.two.signs'
                ),
                801
            );
        }

        QUI\Utils\Project::validateProjectName($name);

        $projects = self::getProjects();

        if (isset($projects[$name])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.project.not.allowed.signs'
                ),
                802
            );
        }

        $name = QUI\Utils\Security\Orthos::clear($name);

        $DataBase = QUI::getDataBase();
        $Table    = $DataBase->table();


        /**
         * Sites and sites relation
         */
        $table_site     = QUI_DB_PRFX.$name.'_'.$lang.'_sites';
        $table_site_rel = QUI_DB_PRFX.$name.'_'.$lang.'_sites_relations';

        $Table->addColumn($table_site, array(
            "id"          => "bigint(20) NOT NULL",
            "name"        => "varchar(200) NOT NULL",
            "title"       => "tinytext NULL",
            "short"       => "text NULL",
            "content"     => "longtext NULL",
            "type"        => "varchar(32) DEFAULT NULL",
            "active"      => "tinyint(1) NOT NULL DEFAULT 0",
            "deleted"     => "tinyint(1) NOT NULL DEFAULT 0",
            "c_date"      => "timestamp NULL DEFAULT NULL",
            "e_date"      => "timestamp NOT NULL DEFAULT NOW() on update NOW()",
            "c_user"      => "int(11) DEFAULT NULL",
            "e_user"      => "int(11) DEFAULT NULL",
            "nav_hide"    => "tinyint(1) NOT NULL DEFAULT 0",
            "order_type"  => "varchar(100) NULL",
            "order_field" => "bigint(20) NULL",
            "extra"       => "text NULL",
        ));

        $Table->addColumn($table_site_rel, array(
            "parent" => "bigint(20) NOT NULL",
            "child"  => "bigint(20) NOT NULL"
        ));

        $Table->setAutoIncrement($table_site, 'id');

        // first site
        $DataBase->insert($table_site, array(
            "id"       => 1,
            "name"     => 'Start',
            "title"    => 'start',
            "short"    => 'Shorttext',
            "content"  => "<p>Welcome to my project</p>",
            "type"     => 'standard',
            "active"   => 1,
            "deleted"  => 0,
            "c_date"   => date('Y-m-d H:i:s'),
            "c_user"   => QUI::getUserBySession()->getId(),
            "e_user"   => QUI::getUserBySession()->getId(),
            "nav_hide" => 0
        ));


        /**
         * Media and media relation
         */
        $table_media     = QUI_DB_PRFX.$name.'_media';
        $table_media_rel = QUI_DB_PRFX.$name.'_media_relations';

        $Table->addColumn($table_media, array(
            "id"           => "bigint(20) NOT NULL",
            "name"         => "varchar(200) NOT NULL",
            "title"        => "tinytext NULL",
            "short"        => "text NULL",
            "type"         => "varchar(32) DEFAULT NULL",
            "active"       => "tinyint(1) NOT NULL DEFAULT 0",
            "deleted"      => "tinyint(1) NOT NULL DEFAULT 0",
            "c_date"       => "timestamp NULL DEFAULT NULL",
            "e_date"       => "timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP",
            "c_user"       => "int(11) DEFAULT NULL",
            "e_user"       => "int(11) DEFAULT NULL",
            "file"         => "text NULL",
            "alt"          => "text NULL",
            "mime_type"    => "text NULL",
            "image_height" => "int(6) default NULL",
            "image_width"  => "int(6) default NULL"
        ));

        $Table->addColumn($table_media_rel, array(
            "parent" => "bigint(20) NOT NULL",
            "child"  => "bigint(20) NOT NULL"
        ));

        // first folder
        $DataBase->insert($table_media, array(
            "id"      => 1,
            "name"    => 'Start',
            "title"   => 'start',
            "short"   => 'Shorttext',
            "type"    => 'folder',
            "file"    => '',
            "active"  => 1,
            "deleted" => 0,
            "c_date"  => date('Y-m-d H:i:s'),
            "c_user"  => QUI::getUserBySession()->getId(),
            "e_user"  => QUI::getUserBySession()->getId()
        ));


        /**
         * Create the file system folders
         */
        QUI\Utils\System\File::mkdir(CMS_DIR.'media/sites/'.$name.'/');
        QUI\Utils\System\File::mkdir(USR_DIR.$name.'/');


        /**
         * Write the config
         */
        if (!file_exists(CMS_DIR.'etc/projects.ini.php')) {
            file_put_contents(CMS_DIR.'etc/projects.ini.php', '');
        }

        $Config = self::getConfig();

        $Config->setSection($name, array(
            'default_lang' => $lang,
            'langs'        => $lang,
            'admin_mail'   => 'support@pcsg.de',
            'template'     => $name,
            'image_text'   => '0',
            'keywords'     => '',
            'description'  => '',
            'robots'       => 'index',
            'author'       => '',
            'publisher'    => '',
            'copyright'    => '',
            'standard'     => '0'
        ));

        if (count($Config->toArray()) <= 1) {
            $Config->setValue($name, 'standard', 1);
        }

        $Config->save();

        // Projekt setup
        $Project = self::getProject($name);
        $Project->setup();

        // Package / Plugin Setup
        QUI::getPluginManager()->setup();

        // Execute the quiqqer/quiqqer setup to create the project related tables
        QUI::getPackage("quiqqer/quiqqer")->setup();

        // Projekt Cache löschen
        QUI\Cache\Manager::clear('QUI::config');

        // project create event
        QUI::getEvents()->fireEvent('createProject', array($Project));

        return $Project;
    }

    /**
     * Delete a project
     *
     * @param \QUI\Projects\Project $Project
     *
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     */
    public static function deleteProject(QUI\Projects\Project $Project)
    {
        Permission::checkProjectPermission(
            'quiqqer.projects.destroy',
            $Project
        );

        $project = $Project->getName();
        $langs   = $Project->getAttribute('langs');

        $DataBase = QUI::getDataBase();
        $Table    = $DataBase->table();

        // delete site tables for all languages
        foreach ($langs as $lang) {
            $table_site     = QUI::getDBTableName($project.'_'.$lang.'_sites');
            $table_site_rel = QUI::getDBTableName(
                $project.'_'.$lang
                .'_sites_relations'
            );
            $table_multi    = QUI::getDBTableName($project.'_multilingual');

            $table_media     = QUI::getDBTableName($project.'_media');
            $table_media_rel = QUI::getDBTableName($project.'_media_relations');


            $Table->delete($table_site);
            $Table->delete($table_site_rel);
            $Table->delete($table_multi);
            $Table->delete($table_media);
            $Table->delete($table_media_rel);
        }

        // delete database tables from plugins
        $packages = QUI::getPackageManager()->getInstalled();

        foreach ($packages as $package) {
            // search database tables
            $databaseXml = OPT_DIR.$package['name'].'/database.xml';

            if (!file_exists($databaseXml)) {
                continue;
            }

            $dbfields = XML::getDataBaseFromXml($databaseXml);

            if (!isset($dbfields['projects'])) {
                continue;
            }

            // for each language
            foreach ($dbfields['projects'] as $table) {
                foreach ($langs as $lang) {
                    $tbl = QUI::getDBTableName(
                        $project.'_'.$lang.'_'.$table['suffix']
                    );

                    $Table->delete($tbl);
                }
            }
        }

        // delete projects permissions
        QUI::getDataBase()->delete(
            QUI::getDBTableName(QUI\Permissions\Manager::TABLE).'2projects',
            array(
                'project' => $project
            )
        );

        QUI::getDataBase()->delete(
            QUI::getDBTableName(QUI\Permissions\Manager::TABLE).'2sites',
            array(
                'project' => $project
            )
        );

        // config schreiben
        $Config = self::getConfig();
        $Config->del($project);
        $Config->save();

        QUI\Cache\Manager::clear('QUI::config');


        // project create event
        QUI::getEvents()->fireEvent('deleteProject', array($project));
    }

    /**
     * Renames the given project
     *
     * @param string $oldName - The projects current name
     * @param string $newName - The new name for the project
     *
     * @throws QUI\Exception
     */
    public static function rename($oldName, $newName)
    {
        QUI\Utils\Project::validateProjectName($newName);

        $Project = self::getProject($oldName);
        // ----------------------------- //
        //              Config           //
        // ----------------------------- //

        // File: etc/projects.ini.php
        $filename = ETC_DIR."projects.ini.php";
        $content  = file_get_contents($filename);

        $content = str_replace($oldName, $newName, $content);
        file_put_contents($filename, $content);


        // File: etc/vhosts.ini.php
        $filename = ETC_DIR."vhosts.ini.php";
        $content  = file_get_contents($filename);

        $content = str_replace($oldName, $newName, $content);
        file_put_contents($filename, $content);


        // ----------------------------- //
        //            Database           //
        // ----------------------------- //

        $tables = array();

        $Stmt = \QUI::getDataBase()->getPDO()->prepare("SHOW TABLES;");
        $Stmt->execute();
        $result = $Stmt->fetchAll();

        foreach ($result as $row) {
            $tables[] = $row[0];
        }

        foreach ($tables as $oldTableName) {
            if (strpos($oldTableName."_", QUI_DB_PRFX.$oldName) === false) {
                continue;
            }


            $newTableName = preg_replace(
                "~^".QUI_DB_PRFX.$oldName."_~m",
                QUI_DB_PRFX.$newName."_",
                $oldTableName
            );


            $sql  = "ALTER TABLE ".$oldTableName." RENAME ".$newTableName.";";
            $Stmt = \QUI::getDataBase()->getPDO()->prepare($sql);

            try {
                $Stmt->execute();
            } catch (\Exception $Exception) {
                QUI\System\Log::writeRecursive("Could not rename Table '".$oldTableName."': ".$Exception->getMessage());
            }
        }


        // ----------------------------- //
        //              Media           //
        // ----------------------------- //

        $sourceDir = CMS_DIR."media/sites/".$oldName;
        $targetDir = CMS_DIR."media/sites/".$newName;

        if (is_dir($sourceDir)) {
            rename($sourceDir, $targetDir);
        }

        // ----------------------------- //
        //              USR           //
        // ----------------------------- //
        $sourceDir = USR_DIR.$oldName;
        $targetDir = USR_DIR.$newName;

        if (is_dir($sourceDir)) {
            rename($sourceDir, $targetDir);
        }

        // -----------------------------//
        //              Cache           //
        // -----------------------------//
        QUI\Cache\Manager::clearAll();


        // ----------------------------- //
        //              Locale           //
        // ----------------------------- //
        if (file_exists(VAR_DIR."locale/localefiles")) {
            unlink(VAR_DIR."locale/localefiles");
        }


        // Remove old translation
        $translationGroup = 'project/'.$oldName;
        $translationVar   = 'title';

        $translation = QUI\Translator::get($translationGroup, $translationVar);
        if (isset($translation[0])) {
            QUI\Translator::delete($translationGroup, $translationVar);
        }


        $translationGroup = 'project/'.$newName;
        $translationVar   = 'title';

        $translation = QUI\Translator::get($translationGroup, $translationVar);
        if (!isset($translation[0])) {
            try {
                QUI\Translator::add($translationGroup, $translationVar);
            } catch (\Exception $Exception) {
                QUI\System\Log::addError("Rename project: Could not add language variable ".$translationGroup."/".$translationVar.": ".$Exception->getMessage());
            }
        }

        QUI\Translator::create();

        // ----------------------------- //
        //              Finish           //
        // ----------------------------- //

        \QUI::getEvents()->fireEvent("projectRenamed", array(
            $Project,
            $oldName,
            $newName
        ));

        unset(self::$projects[$oldName]);
    }

    /**
     * Return all templates which are related to the project
     * the vhost templates are included
     *
     * @param \QUI\Projects\Project $Project
     *
     * @return array
     */
    public static function getRelatedTemplates(QUI\Projects\Project $Project)
    {
        $result    = array();
        $templates = array();
        $project   = $Project->getName();

        if ($Project->getAttribute('template')) {
            $result[]                                      = $Project->getAttribute('template');
            $templates[$Project->getAttribute('template')] = true;
        }

        // vhosts und templates schauen
        $vhosts = QUI::getRewrite()->getVHosts();

        foreach ($vhosts as $vhost) {
            if (!isset($vhost['project'])) {
                continue;
            }

            if ($vhost['project'] != $project) {
                continue;
            }

            if (!isset($vhost['template'])) {
                continue;
            }

            if (isset($templates[$vhost['template']])) {
                continue;
            }

            $templates[$vhost['template']] = true;
            $result[]                      = $vhost['template'];
        }

        return $result;
    }

    /**
     * Return all settings.xml which are related to the project
     * eq: all settings.xml from templates
     *
     * @param \QUI\Projects\Project $Project
     *
     * @return array
     */
    public static function getRelatedSettingsXML(QUI\Projects\Project $Project)
    {
        $cache = 'qui/projects/'.$Project->getName().'/relatedSettingsXml';

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Exception $Exception) {
        }

        $list     = array();
        $packages = QUI::getPackageManager()->getInstalled();

        $templates = self::getRelatedTemplates($Project);
        $templates = array_flip($templates);

        // read template config
        foreach ($packages as $package) {
            // if the package is a quiqqer template,
            if ($package['type'] == 'quiqqer-template') {
                // note only related templates
                if (!isset($templates[$package['name']])) {
                    continue;
                }
            }

            $file = OPT_DIR.$package['name'].'/settings.xml';

            if (!file_exists($file)) {
                continue;
            }

            $Dom  = XML::getDomFromXml($file);
            $Path = new \DOMXPath($Dom);

            $Settings = $Path->query("//quiqqer/project/settings");

            if ($Settings->length) {
                $list[] = $file;
            }
        }

        // direct - project settings
        $projectSettings = USR_DIR.$Project->getName().'/settings.xml';

        if (file_exists($projectSettings)) {
            $Dom  = XML::getDomFromXml($projectSettings);
            $Path = new \DOMXPath($Dom);

            $Settings = $Path->query("//quiqqer/project/settings");

            if ($Settings->length) {
                $list[] = $projectSettings;
            }
        }

        QUI\Cache\Manager::set('qui/projects/', $list);

        return $list;
    }

    /**
     * Search a project
     *
     * @param array $params - Search params
     *                      'search' => 'search string',
     *                      'limit'  => 5,
     *                      'page'   => 1
     *
     * @return array
     *
     * @throws QUI\Exception
     */
    public static function search($params)
    {
        if (!isset($params['search'])) {
            return array();
        }

        $search = $params['search'];

        $result = array();
        $list   = self::getConfig()->toArray();

        foreach ($list as $project => $entry) {
            if (!empty($search) && strpos($project, $search) === false) {
                continue;
            }

            $langs = explode(',', $entry['langs']);

            foreach ($langs as $lang) {
                $result[] = array(
                    'project' => $project,
                    'lang'    => $lang
                );
            }
        }

        return $result;
    }
}
