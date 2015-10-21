<?php

/**
 * This file contains the \QUI\Projects\Manager
 */

namespace QUI\Projects;

use QUI;
use QUI\Rights\Permission;
use QUI\Utils\Security\Orthos;
use QUI\Utils\DOM;


/**
 * The Project Manager
 * The main object to get a project
 *
 * @author     www.pcsg.de (Henning Leutz)
 * @licence    For copyright and license information, please view the /README.md
 *
 * @event      onProjectConfigSave [ String project, Array config ]
 * @event      onCreateProject [ String \QUI\Projects\Project ]
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
    static $Config = null;

    /**
     * laoded projects
     *
     * @var array
     */
    static $projects = array();

    /**
     * standard project
     *
     * @var \QUI\Projects\Project
     */
    static $Standard = null;

    /**
     * projects.ini
     *
     * @return \QUI\Config
     */
    static function getConfig()
    {
        return QUI::getConfig('etc/projects.ini');
    }

    /**
     * set configuration for a project
     *
     * @param String $project
     * @param Array $params
     */
    static function setConfigForProject($project, $params)
    {
        $Project = self::getProject($project);

        Permission::checkProjectPermission(
            'quiqqer.projects.setconfig',
            $Project
        );

        $Config   = self::getConfig();
        $projects = $Config->toArray();

        // $config
        $config     = self::getProjectConfigList($Project);
        $old_config = $config;

        if (isset($projects[$project])) {
            $old_config = $projects[$project];
        }

        // generate new config for the project
        foreach ($config as $key => $value) {

            if (!isset($old_config[$key])) {
                continue;
            }

            if (isset($params[$key])) {
                $str = Orthos::removeHTML($params[$key]);
                $str = Orthos::clearPath($str);

                $config[$key] = $str;
                continue;
            }

            $str = Orthos::removeHTML($value);
            $str = Orthos::clearPath($str);

            $config[$key] = $str;
        }

        // doppelte sprachen filtern
        $langs = explode(',', $config['langs']);
        $langs = array_unique($langs);

        $config['langs'] = implode(',', $langs);

        $Config->setSection($project, $config);
        $Config->save();

        QUI::getEvents()
            ->fireEvent('projectConfigSave', array($project, $config));

        // remove the project from the temp
        if (self::$projects[$project]) {
            unset(self::$projects[$project]);
        }

        // execute the project setup
        $Project = self::getProject($project);
        $Project->setup();

        /**
         * clear media cache
         * eq: if watermark settings changed
         *
         * @param Array $config
         * @param Array $old_config
         * @param Project $Project
         */
        function clearMediaCache($config, $old_config, Project $Project)
        {
            if (!isset($config["media_watermark"])
                && !isset($config["media_watermark_position"])
                && !isset($config["media_image_library"])
            ) {
                return;
            }

            if (isset($config["media_watermark"])
                && isset($old_config['media_watermark'])
                && $config["media_watermark"] != $old_config['media_watermark']
            ) {
                // clear cache
                $Project->getMedia()->clearCache();
                return;
            }

            if (isset($config["media_watermark_ratio"])
                && isset($old_config['media_watermark_ratio'])
                && $config["media_watermark_ratio"] != $old_config['media_watermark_ratio']
            ) {
                // clear cache
                $Project->getMedia()->clearCache();
                return;
            }

            if (isset($config["media_watermark_position"])
                && isset($old_config['media_watermark_position'])
                && $config["media_watermark_position"]
                   != $old_config['media_watermark_position']
            ) {
                // clear cache
                $Project->getMedia()->clearCache();
                return;
            }

            if (isset($config["media_image_library"])
                && isset($old_config['media_image_library'])
                && $config["media_image_library"]
                   != $old_config['media_image_library']
            ) {
                // clear cache
                $Project->getMedia()->clearCache();
                return;
            }
        }

        clearMediaCache($config, $old_config, $Project);

        // if this project should be the standard,
        // all other projects are not
        if (!isset($config['standard']) || $config['standard'] != 1) {
            return;
        }

        $projects = $Config->toArray();

        foreach ($projects as $_project => $settings) {
            if ($_project != $project) {
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
     * @return Array
     */
    static function getProjectConfigList(QUI\Projects\Project $Project)
    {
        $cache = 'qui/projects/' . $Project->getName() . '/configList';

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
            "placeholder"              => ""
        );

        // settings.xml
        $settingsXml = self::getRelatedSettingsXML($Project);

        foreach ($settingsXml as $file) {
            $Dom  = QUI\Utils\XML::getDomFromXml($file);
            $Path = new \DOMXPath($Dom);

            $settingsList = $Path->query("//project/settings");

            for ($i = 0, $len = $settingsList->length; $i < $len; $i++) {
                /* @var $Settings \DOMElement */
                $Settings = $settingsList->item($i);
                $sections = DOM::getConfigParamsFromDOM($Settings);

                $settingsName = $Settings->getAttribute('name');

                if (!empty($settingsName)) {
                    $settingsName = $settingsName . '.';
                }

                foreach ($sections as $section => $entry) {
                    foreach ($entry as $key => $param) {
                        $config[$settingsName . $section . '.' . $key] = '';

                        if (isset($param['default'])) {
                            $config[$settingsName . $section . '.' . $key]
                                = $param['default'];
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
     * @return Integer
     */
    static function count()
    {
        $Config = self::getConfig();
        $config = $Config->toArray();

        return count($config);
    }

    /**
     * Decode project data
     * Decode a project json string to a Project or decode a project array to a Project
     *
     * @param String|Array $project - project data
     *
     * @return \QUI\Projects\Project
     * @throws \QUI\Exception
     */
    static function decode($project)
    {
        if (is_string($project)) {
            $project = json_decode($project, true);
        }

        if (!isset($project['name'])) {
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
    static function get()
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
     * @param String $project - Project name
     * @param String|Bool $lang - Project lang, optional (if not set, the standard language used)
     * @param String|Bool $template - used template, optional (if not set, the standard templaed used)
     *
     * @return \QUI\Projects\Project
     */
    static function getProject($project, $lang = false, $template = false)
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
            self::$projects[$project]['_standard']
                = new QUI\Projects\Project($project);

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
     * @param Bool $asobject - Als Objekte bekommen, default = false
     *
     * @return Array
     */
    static function getProjects($asobject = false)
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
     * Standard Projekt bekommen
     *
     * @return \QUI\Projects\Project
     * @throws QUI\Exception
     */
    static function getStandard()
    {
        if (!is_null(self::$Standard)) {
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

        if (is_null(self::$Standard)) {
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
     * @param String $name - Project name
     * @param String $lang - Project lang
     *
     * @return \QUI\Projects\Project
     * @throws \QUI\Exception
     *
     * @todo noch einmal anschauen und übersichtlicher schreiben
     */
    static function createProject($name, $lang)
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

        $not_allowed_signs = array(
            '-',
            '.',
            ',',
            ':',
            ';',
            '#',
            '`',
            '!',
            '§',
            '$',
            '%',
            '&',
            '/',
            '?',
            '<',
            '>',
            '=',
            '\'',
            '"'
        );

        if (preg_match("@[-.,:;#`!§$%&/?<>\=\'\" ]@", $name)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.project.not.allowed.signs',
                    array(
                        'signs' => implode(' ', $not_allowed_signs)
                    )
                ),
                802
            );
        }

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
        $Table    = $DataBase->Table();


        /**
         * Sites and sites relation
         */
        $table_site     = QUI_DB_PRFX . $name . '_' . $lang . '_sites';
        $table_site_rel = QUI_DB_PRFX . $name . '_' . $lang . '_sites_relations';

        $Table->appendFields($table_site, array(
            "id"          => "bigint(20) NOT NULL",
            "name"        => "varchar(200) NOT NULL",
            "title"       => "tinytext",
            "short"       => "text",
            "content"     => "longtext",
            "type"        => "varchar(32) default NULL",
            "active"      => "tinyint(1) NOT NULL",
            "deleted"     => "tinyint(1) NOT NULL",
            "c_date"      => "timestamp NULL default NULL",
            "e_date"      => "timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP",
            "c_user"      => "int(11) default NULL",
            "e_user"      => "int(11) default NULL",
            "nav_hide"    => "tinyint(1) NOT NULL",
            "order_type"  => "varchar(100) default NULL",
            "order_field" => "bigint(20) default NULL",
            "extra"       => "text default NULL",
        ));

        $Table->appendFields($table_site_rel, array(
            "parent" => "bigint(20) NOT NULL",
            "child"  => "bigint(20) NOT NULL"
        ));

        $Table->setAutoIncrement($table_site, 'id');

        // first site
        $DataBase->insert($table_site, array(
            "id"          => 1,
            "name"        => 'Start',
            "title"       => 'start',
            "short"       => 'Shorttext',
            "content"     => "<p>Welcome to my project</p>",
            "type"        => 'standard',
            "active"      => 1,
            "deleted"     => 0,
            "c_date"      => date('Y-m-d H:i:s'),
            "c_user"      => QUI::getUserBySession()->getId(),
            "e_user"      => QUI::getUserBySession()->getId(),
            "nav_hide"    => '',
            "order_type"  => "",
            "order_field" => ""
        ));


        /**
         * Media and media relation
         */
        $table_media     = QUI_DB_PRFX . $name . '_media';
        $table_media_rel = QUI_DB_PRFX . $name . '_media_relations';

        $Table->appendFields($table_media, array(
            "id"           => "bigint(20) NOT NULL",
            "name"         => "varchar(200) NOT NULL",
            "title"        => "tinytext",
            "short"        => "text",
            "type"         => "varchar(32) default NULL",
            "active"       => "tinyint(1) NOT NULL",
            "deleted"      => "tinyint(1) NOT NULL",
            "c_date"       => "timestamp NULL default NULL",
            "e_date"       => "timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP",
            "c_user"       => "int(11) default NULL",
            "e_user"       => "int(11) default NULL",
            "file"         => "text",
            "alt"          => "text",
            "mime_type"    => "text",
            "image_height" => "int(6) default NULL",
            "image_width"  => "int(6) default NULL"
        ));

        $Table->appendFields($table_media_rel, array(
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
        QUI\Utils\System\File::mkdir(CMS_DIR . 'media/sites/' . $name . '/');
        QUI\Utils\System\File::mkdir(USR_DIR . $name . '/');


        /**
         * Write the config
         */
        if (!file_exists(CMS_DIR . 'etc/projects.ini.php')) {
            file_put_contents(CMS_DIR . 'etc/projects.ini.php', '');
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

        if (count($Config->toArray()) == 1) {
            $Config->setSection($name, 'standard', 1);
        }

        $Config->save();

        // Projekt setup
        $Project = self::getProject($name);
        $Project->setup();

        // Package / Plugin Setup
        QUI::getPluginManager()->setup($Project);

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
     */
    static function deleteProject(QUI\Projects\Project $Project)
    {
        Permission::checkProjectPermission(
            'quiqqer.projects.destroy',
            $Project
        );

        $project = $Project->getName();
        $langs   = $Project->getAttribute('langs');

        $DataBase = QUI::getDataBase();
        $Table    = $DataBase->Table();

        // delete site tables for all languages
        foreach ($langs as $lang) {
            $table_site     = QUI::getDBTableName($project . '_' . $lang . '_sites');
            $table_site_rel = QUI::getDBTableName($project . '_' . $lang
                                                  . '_sites_relations');
            $table_multi    = QUI::getDBTableName($project . '_multilingual');

            $table_media     = QUI::getDBTableName($project . '_media');
            $table_media_rel = QUI::getDBTableName($project . '_media_relations');


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
            $databaseXml = OPT_DIR . $package['name'] . '/database.xml';

            if (!file_exists($databaseXml)) {
                continue;
            }

            $dbfields = QUI\Utils\XML::getDataBaseFromXml($databaseXml);

            if (!isset($dbfields['projects'])) {
                continue;
            }

            // for each language
            foreach ($dbfields['projects'] as $table) {
                foreach ($langs as $lang) {
                    $tbl = QUI::getDBTableName(
                        $project . '_' . $lang . '_' . $table['suffix']
                    );

                    $Table->delete($tbl);
                }
            }
        }

        // delete projects permissions
        QUI::getDataBase()->delete(
            QUI::getDBTableName(QUI\Rights\Manager::TABLE) . '2projects',
            array(
                'project' => $project
            )
        );

        QUI::getDataBase()->delete(
            QUI::getDBTableName(QUI\Rights\Manager::TABLE) . '2sites',
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
     * Return all templates which are related to the project
     * the vhost templates are included
     *
     * @param \QUI\Projects\Project $Project
     *
     * @return Array
     */
    static function getRelatedTemplates(QUI\Projects\Project $Project)
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
     * @return Array
     */
    static function getRelatedSettingsXML(QUI\Projects\Project $Project)
    {
        $cache = 'qui/projects/' . $Project->getName() . '/relatedSettingsXml';

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

            $file = OPT_DIR . $package['name'] . '/settings.xml';

            if (!file_exists($file)) {
                continue;
            }

            $Dom  = QUI\Utils\XML::getDomFromXml($file);
            $Path = new \DOMXPath($Dom);

            $Settings = $Path->query("//quiqqer/project/settings");

            if ($Settings->length) {
                $list[] = $file;
            }
        }

        // direct - project settings
        $projectSettings = USR_DIR . $Project->getName() . '/settings.xml';

        if (file_exists($projectSettings)) {
            $Dom  = QUI\Utils\XML::getDomFromXml($projectSettings);
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
     * @param Array $params - Search params
     *                      'search' => 'search string',
     *                      'limit'  => 5,
     *                      'page'   => 1
     *
     * @return Array
     */
    static function search($params)
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
