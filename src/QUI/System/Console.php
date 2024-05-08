<?php

/**
 * This file contains System_Console
 */

namespace QUI\System;

use Exception;
use League\CLImate\CLImate;
use QUI;
use QUI\Utils\Security\Orthos;

use function array_flip;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function chr;
use function class_exists;
use function count;
use function date;
use function define;
use function explode;
use function fgets;
use function file_exists;
use function fileowner;
use function implode;
use function in_array;
use function is_array;
use function is_null;
use function is_object;
use function key;
use function ksort;
use function ob_end_clean;
use function php_sapi_name;
use function phpversion;
use function posix_geteuid;
use function posix_getpwuid;
use function realpath;
use function sort;
use function str_replace;
use function strpos;
use function strtolower;
use function time;
use function trim;

use const PHP_EOL;

/**
 * The QUIQQER Console
 *
 * With the console you can start tools / crons in the shell
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @author  www.pcsg.de (Moritz Scholz)
 * @licence For copyright and license information, please view the /README.md
 */
class Console
{
    /**
     * The current text color
     *
     * @var string|bool
     */
    protected string|bool $current_color = false;

    /**
     * the current background color
     *
     * @var string|bool
     */
    protected string|bool $current_bg = false;

    /**
     * All available text colors
     *
     * @var array
     */
    protected array $colors = [
        'black' => '0;30',
        'dark_gray' => '1;30',
        'blue' => '0;34',
        'light_blue' => '1;34',
        'green' => '0;32',
        'light_green' => '1;32',
        'cyan' => '0;36',
        'light_cyan' => '1;36',
        'red' => '1;31',
        'light_red' => '2;31',
        'purple' => '0;35',
        'light_purple' => '1;35',
        'brown' => '0;33',
        'yellow' => '1;33',
        'light_gray' => '0;37',
        'white' => '1;37',
        'black_u' => '4;30',
        'red_u' => '4;31',
        'green_u' => '4;32',
        'yellow_u' => '4;33',
        'blue_u' => '4;34',
        'purple_u' => '4;35',
        'cyan_u' => '4;36',
        'white_u' => '4;37'
    ];

    /**
     * All available background colors
     *
     * @var array
     */
    protected array $bg = [
        'black' => '40',
        'red' => '41',
        'green' => '42',
        'yellow' => '43',
        'blue' => '44',
        'magenta' => '45',
        'cyan' => '46',
        'light_gray' => '47'
    ];

    /**
     * CLI arguments
     *
     * @var array
     */
    protected array $arguments = [];

    /**
     * @var null|QUI\Interfaces\Users\User
     */
    protected ?QUI\Interfaces\Users\User $User = null;

    /**
     * All available console tools
     *
     * @var array
     */
    private array $tools = [];

    /**
     * @var array All available tools, but grouped
     */
    private array $groupedTools = [];

    /**
     * List of system tools
     * Tools which are called with the SystemUser
     *
     * @var array
     */
    private array $systemTools = [
        'clear-all-quiqqer-cache',
        'clear-cache',
        'clear-cache-path',
        'clear-tmp',
        'clear-sessions',
        'clear-lock',
        'cron',
        'password-reset',
        'setup',
        'update',
        'package',
        'licence',
        'htaccess',
        'composer'
    ];

    /**
     * Console parameter
     *
     * @var array
     */
    private array $argv;

    /**
     * constructor
     * @throws QUI\Exception
     */
    public function __construct()
    {
        // check locale
        $languages = QUI::availableLanguages();
        $languages = array_flip($languages);

        $locale = QUI::getLocale()->getCurrent();

        if (!isset($languages[$locale])) {
            if (isset($languages['en'])) {
                QUI::getLocale()->setCurrent('en');
            } elseif (isset($languages['de'])) {
                QUI::getLocale()->setCurrent('de');
            } else {
                QUI::getLocale()->setCurrent(key($languages));
            }
        }

        $this->title();


        if (!isset($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = '';
        }

        if (!isset($_SERVER['argv'])) {
            $this->writeLn("Cannot use Consoletools");
            exit;
        }

        // read argv params
        $params = $this->getArguments();
        $this->argv = $params;

        $this->read();

        // read system tools
        $tools = $this->get(true);
        $systemTools = $this->systemTools;

        foreach ($tools as $tool => $Tool) {
            if ($Tool->isSystemTool()) {
                $systemTools[] = $tool;
            }
        }

        $systemTools = array_unique($systemTools);

        sort($systemTools);
        $this->systemTools = $systemTools;

        $args = $this->readArgv();
        $isSystemTool = key($args);

        if (in_array($isSystemTool, $this->systemTools)) {
            $this->setArgument('#system-tool', $isSystemTool);
        }

        // check execute permissions with process user
        $ignorePermCheck = $this->getArgument('ignore-file-permissions');
        $processUser = posix_getpwuid(posix_geteuid());
        $processUser = $processUser['name'];

        $owner = fileowner(__FILE__);
        $owner = posix_getpwuid($owner);
        $owner = $owner['name'];

        if (!$ignorePermCheck && $owner !== $processUser) {
            $this->write(
                QUI::getLocale()->get('quiqqer/core', 'exception.console.execute.user', [
                    'user' => $processUser,
                    'owner' => $owner,
                ]),
                'red'
            );

            $this->clearMsg();
            $this->writeLn();
            $this->writeLn();
            $this->write(QUI::getLocale()->get('quiqqer/core', 'console.execute-user.question'));

            $input = $this->readInput();

            if ($input !== 'yes') {
                exit;
            }
        }

        if ($this->getArgument('#system-tool')) {
            $this->executeSystemTool();
            exit;
        }


        if (isset($params['help']) && !isset($params['tool'])) {
            $this->help();
            exit;
        }


        // system tools
        if (empty($params)) {
            $this->help();
        }

        if (
            !$this->getArgument('--login')
            && !$this->getArgument('--username')
            && !$this->getArgument('--listtools')
        ) {
            if (!empty($args)) {
                $this->displayToolsForGroups(key($args));
            }

            return;
        }

        try {
            $this->authenticate();
        } catch (QUI\Exception $Exception) {
            QUI::getEvents()->fireEvent('userCliLoginError', [$this->getArgument('username'), $Exception]);

            $this->writeLn($Exception->getMessage() . "\n\n", 'red');
            exit;
        }

        if (is_null($this->User) || !$this->User->getUUID()) {
            QUI::getEvents()->fireEvent('userCliLoginError', [$this->getArgument('username')]);

            $this->writeLn("Login incorrect\n\n", 'red');
            exit;
        }

        QUI\Permissions\Permission::setUser($this->User);

        QUI::getEvents()->fireEvent('userCliLogin', [$this->User]);

        if (!QUI\Permissions\Permission::hasPermission('quiqqer.system.console')) {
            $this->writeLn("Missing rights to use the console\n\n", 'red');
            $this->clearMsg();
            exit;
        }

        if (isset($params['listtools'])) {
            $this->help();
        }

        if (!isset($params['tool']) && !isset($params['listtools'])) {
            $this->writeLn("\n");
            $this->readToolFromShell();
        }
    }

    /**
     * QUIQQER Console title
     * output the main quiqqer console info
     */
    public function title()
    {
        $params = $this->readArgv();
        $version = QUI::getPackageManager()->getVersion();
        $year = date('Y');

        $lastUpdate = QUI::getPackageManager()->getLastUpdateDate();
        $lastUpdate = QUI::getLocale()->formatDate($lastUpdate);

        if (isset($params['--noLogo'])) {
            return;
        }

        $str = '
  _______          _________ _______  _______  _______  _______
 (  ___  )|\     /|\__   __/(  ___  )(  ___  )(  ____ \(  ____ )
 | (   ) || )   ( |   ) (   | (   ) || (   ) || (    \/| (    )|
 | |   | || |   | |   | |   | |   | || |   | || (__    | (____)|
 | |   | || |   | |   | |   | |   | || |   | ||  __)   |     __)
 | | /\| || |   | |   | |   | | /\| || | /\| || (      | (\ (
 | (_\ \ || (___) |___) (___| (_\ \ || (_\ \ || (____/\| ) \ \__
 (____\/_)(_______)\_______/(____\/_)(____\/_)(_______/|/   \__/


 Welcome to QUIQQER Version ' . $version . ' - Last Update: ' . $lastUpdate . ' - PHP Version: ' . phpversion() . '
';

        $this->message($str, 'green', 'white');
        $this->clearMsg();

        $licenceText = '
 QUIQQER Copyright(C) ' . $year . '  PCSG - Computer & Internet Service OHG - www.pcsg.de
 This program comes with ABSOLUTELY NO WARRANTY; for details type `./console licence`.
 This is free software, and you are welcome to redistribute it under certain conditions;
 visit www.quiqqer.com for details.

                               ';

        $this->message($licenceText, 'cyan', 'white');
        $this->clearMsg();
    }

    /**
     * Read the argv params
     *
     * @return array
     */
    protected function readArgv(): array
    {
        // Vars löschen die Probleme bereiten können
        $_REQUEST = [];
        $_POST = [];
        $_GET = [];

        if (isset($_SERVER['argv'][0])) {
            unset($_SERVER['argv'][0]);
        }

        $params = [];

        // Parameter auslesen
        foreach ($_SERVER['argv'] as $argv) {
            if (str_contains($argv, '=')) {
                $var = explode('=', $argv);

                if (isset($var[0]) && isset($var[1])) {
                    $params[$var[0]] = $var[1];
                }
            } else {
                $params[$argv] = true;
            }
        }

        return $params;
    }

    /**
     * Output a message
     *
     * @param string $msg - Message to output
     * @param string|boolean $color - (optional) text color
     * @param string|boolean $bg - (optional) background color
     */
    public function message(string $msg, $color = false, $bg = false)
    {
        if ($color) {
            $this->current_color = $color;
        }

        if ($bg) {
            $this->current_bg = $bg;
        }

        if (isset($this->colors[$this->current_color])) {
            echo "\033[" . $this->colors[$this->current_color] . "m";
        }

        if (isset($this->bg[$this->current_bg])) {
            echo "\033[" . $this->bg[$this->current_bg] . "m";
        }

        echo $msg;

        $this->resetMsg();
    }

    /**
     * reset the message color
     */
    public function resetMsg()
    {
        echo "\033[0m";
    }

    /**
     * reset the message and background color and reset the color settings
     */
    public function clearMsg()
    {
        $this->current_color = false;
        $this->current_bg = false;

        echo "\033[0m";
    }

    /**
     * Write a new line
     *
     * @param string $msg - (optional) the printed message
     * @param string|boolean $color - (optional) textcolor
     * @param string|boolean $bg - (optional) background color
     */
    public function writeLn(string $msg = '', $color = false, $bg = false)
    {
        $this->message(PHP_EOL . $msg, $color, $bg);

        if (php_sapi_name() === 'cli') {
            @flush();
            @ob_flush();
        }
    }

    /**
     * Return the CLI arguments
     *
     * @return array
     */
    public function getArguments(): array
    {
        if (!empty($this->arguments)) {
            return $this->arguments;
        }

        $args = $this->readArgv();

        foreach ($args as $arg => $value) {
            $this->setArgument($arg, $value);
        }

        return $this->arguments;
    }

    /**
     * Set CLI arguments
     *
     * @param string $argument
     * @param string $value
     */
    public function setArgument(string $argument, string $value)
    {
        $argument = trim($argument, '-');

        $this->arguments[$argument] = $value;
    }

    /**
     * Read all tools and include it
     *
     * @return void
     * @throws QUI\Exception
     */
    private function read(): void
    {
        if (!empty($this->tools)) {
            return;
        }

        // Standard Konsoletools
        $path = LIB_DIR . 'QUI/System/Console/Tools/';
        $files = QUI\Utils\System\File::readDir($path, true);

        for ($i = 0, $len = count($files); $i < $len; $i++) {
            if (!file_exists($path . $files[$i])) {
                continue;
            }

            $this->includeClasses($files[$i], $path);
        }

        // look at console tools at plugins
        $PackageManager = QUI::getPackageManager();
        $plugins = $PackageManager->getInstalled();

        $tools = [];

        foreach ($plugins as $plugin) {
            $dir = OPT_DIR . $plugin['name'];

            if (!file_exists($dir . '/console.xml')) {
                continue;
            }

            $tools = array_merge(
                $tools,
                QUI\Utils\Text\XML::getConsoleToolsFromXml($dir . '/console.xml')
            );
        }

        // look at console tools at projects
        $ProjectManager = QUI::getProjectManager();
        $projects = $ProjectManager->getProjects();

        foreach ($projects as $project) {
            $dir = USR_DIR . $project;

            if (!file_exists($dir . '/console.xml')) {
                continue;
            }

            $tools = array_merge(
                $tools,
                QUI\Utils\Text\XML::getConsoleToolsFromXml($dir . '/console.xml')
            );
        }


        // init tools
        foreach ($tools as $cls) {
            if (!class_exists($cls)) {
                continue;
            }

            /* @var $Tool Console\Tool */
            $Tool = new $cls();
            $Tool->setAttribute('parent', $this);

            if ($this->argv) {
                foreach ($this->argv as $key => $value) {
                    $Tool->setArgument($key, $value);
                }
            }

            $this->tools[$Tool->getName()] = $Tool;
        }

        // grouping
        $groups = [];

        foreach ($this->tools as $name => $Tool) {
            if (!str_contains($name, ':')) {
                continue;
            }

            $name = explode(':', $name);

            $groups[$name[0]][$name[1]] = $Tool;
        }

        $this->groupedTools = $groups;
    }

    /**
     * Include the tool class
     *
     * @param string $file
     * @param string $dir
     *
     * @throws QUI\Exception
     */
    protected function includeClasses(string $file, string $dir)
    {
        $file = Orthos::clearPath(realpath($dir . $file));

        if (!file_exists($file)) {
            throw new QUI\Exception('console tool not exists');
        }

        require_once $file;

        $class = str_replace('.php', '', $file);
        $class = explode(LIB_DIR, $class);
        $class = str_replace('/', '\\', $class[1]);

        if (!class_exists($class)) {
            return;
        }

        /* @var $Tool Console\Tool */
        $Tool = new $class();
        $Tool->setAttribute('parent', $this);

        if ($this->argv) {
            foreach ($this->argv as $key => $value) {
                $Tool->setArgument($key, $value);
            }
        }

        $this->tools[$Tool->getName()] = $Tool;
    }

    /**
     * Return a tool
     *
     * @param boolean|string $tool - boolean true = all Tools | string = specific tool
     *
     * @return array|Console\Tool|bool
     */
    public function get($tool)
    {
        if (isset($this->tools[$tool]) && is_object($this->tools[$tool])) {
            return $this->tools[$tool];
        }

        if ($tool) {
            return $this->tools;
        }

        return false;
    }

    /**
     * Return the CLI argument
     *
     * @param string $argument
     *
     * @return mixed|null
     */
    public function getArgument(string $argument)
    {
        $argument = trim($argument, '-');

        return $this->arguments[$argument] ?? null;
    }

    /**
     * alternative for message()
     *
     * @param string $msg - Message to output
     * @param string|boolean $color - (optional) text color
     * @param string|boolean $bg - (optional) background color
     */
    public function write(string $msg, $color = false, $bg = false)
    {
        $this->message($msg, $color, $bg);
    }

    /**
     * Read the input from the user -> STDIN
     *
     * @return string
     */
    public function readInput(): string
    {
        return trim(fgets(STDIN));
    }

    /**
     * Exceute the system tool
     * @throws QUI\Exception
     */
    protected function executeSystemTool()
    {
        if (php_sapi_name() != 'cli') {
            throw new QUI\Exception([
                'quiqqer/core',
                'exception.console.execute.only.in.cli'
            ]);
        }

        define('SYSTEM_INTERN', true);

        QUI\Permissions\Permission::setUser(
            QUI::getUsers()->getSystemUser()
        );

        $help = $this->getArgument('help');

        $displaySystemToolHelp = function ($tool) {
            $self = $this;
            $description = QUI::getLocale()->get(
                'quiqqer/core',
                'console.systemtool.' . $tool
            );

            $self->writeLn($tool . ':');
            $self->writeLn($description);
        };

        switch ($this->getArgument('#system-tool')) {
            case 'clear-all-quiqqer-cache':
                if ($help) {
                    $displaySystemToolHelp($this->getArgument('#system-tool'));

                    return;
                }

                QUI\Cache\Manager::clearAll();
                QUI::getTemp()->moveToTemp(VAR_DIR . 'cache');
                QUI::getTemp()->moveToTemp(VAR_DIR . 'sessions');
                QUI\Cache\Manager::clearCompleteQuiqqerCache();
                break;

            case 'clear-cache':
                if ($help) {
                    $displaySystemToolHelp($this->getArgument('#system-tool'));

                    return;
                }

                QUI\Cache\Manager::clearCompleteQuiqqerCache();
                QUI::getTemp()->moveToTemp(VAR_DIR . 'cache');
                break;

            case 'clear-cache-path':
                if ($help) {
                    $displaySystemToolHelp($this->getArgument('#system-tool'));

                    return;
                }

                $path = $this->getArgument('path');

                if (empty($path)) {
                    $this->writeLn('Missing --path. Pleas use --path="my/cache/path"', 'red');
                    $this->writeLn();
                    $this->resetMsg();

                    return;
                }

                QUI\Cache\Manager::clear($path);
                break;

            case 'clear-tmp':
                if ($help) {
                    $displaySystemToolHelp($this->getArgument('#system-tool'));

                    return;
                }

                QUI::getTemp()->clear();
                break;

            case 'clear-sessions':
                if ($help) {
                    $displaySystemToolHelp($this->getArgument('#system-tool'));

                    return;
                }

                QUI::getTemp()->moveToTemp(VAR_DIR . 'sessions');
                break;

            case 'clear-lock':
                if ($help) {
                    $displaySystemToolHelp($this->getArgument('#system-tool'));

                    return;
                }

                QUI::getTemp()->moveToTemp(VAR_DIR . 'lock');
                break;

            case 'cron':
                if ($help) {
                    $displaySystemToolHelp($this->getArgument('#system-tool'));

                    return;
                }

                QUI::getPackage('quiqqer/cron');

                // locking
                $lockKey = 'cron-execution';
                $Package = QUI::getPackage('quiqqer/cron');

                if (QUI\Lock\Locker::isLocked($Package, $lockKey, null, false)) {
                    $time = QUI\Lock\Locker::getLockTime($Package, $lockKey);

                    if ($time < 0) {
                        $this->writeLn(
                            'Crons cannot be executed because another instance is already executing crons.',
                            'red'
                        );

                        $this->resetMsg();
                        $this->writeLn();
                        exit(1);
                    }
                }

                $CronManager = new QUI\Cron\Manager();
                $CronManager->execute();

                break;

            case 'password-reset':
                if ($help) {
                    $displaySystemToolHelp($this->getArgument('#system-tool'));

                    return;
                }

                $this->passwordReset();
                break;

            case 'setup':
                $this->setArgument('#system-tool', 'quiqqer:setup');
                break;

            case 'update':
                $this->setArgument('#system-tool', 'quiqqer:update');
                break;

            case 'package':
                $this->setArgument('#system-tool', 'quiqqer:package');
                break;

            case 'htaccess':
                $this->setArgument('#system-tool', 'quiqqer:htaccess');
                break;

            case 'licence':
                $this->setArgument('#system-tool', 'quiqqer:licence');
                break;
        }

        $Tool = $this->get($this->getArgument('#system-tool'));

        if ($Tool && !is_array($Tool)) {
            if ($help) {
                $Tool->outputHelp();

                return;
            }

            $Tool->setAttribute('parent', $this);

            if ($this->argv) {
                foreach ($this->argv as $key => $value) {
                    $Tool->setArgument($key, $value);
                }
            }

            $Tool->execute();
        }

        $this->writeLn('Everything is done. Thank you for using QUIQQER', 'green');
        $this->resetMsg();
        $this->writeLn();
    }

    /**
     * clear the console (all colors)
     */
    public function clear()
    {
        array_map(function ($a) {
            print chr($a);
        }, [27, 91, 72, 27, 91, 50, 74]);
    }

    /**
     * Initiates a password reset
     * @throws \QUI\Users\Exception
     */
    protected function passwordReset()
    {
        $this->writeLn(
            QUI::getLocale()->get(
                "quiqqer/core",
                "console.tool.passwordreset.header"
            )
        );

        $this->writeLn(
            QUI::getLocale()->get(
                "quiqqer/core",
                "console.tool.passwordreset.warning"
            ),
            "yellow"
        );
        $this->clearMsg();

        // Get user Input
        $this->writeLn(
            QUI::getLocale()->get(
                "quiqqer/core",
                "console.tool.passwordreset.prompt.username"
            )
        );

        $username = $this->readInput();

        try {
            $User = QUI::getUsers()->getUserByName($username);
        } catch (Exception) {
            $this->writeLn(
                QUI::getLocale()->get(
                    "quiqqer/core",
                    "console.tool.passwordreset.user.not.found"
                ),
                "red"
            );
            $this->write("\n");
            exit;
        }

        // Confirmation
        $this->writeLn(
            QUI::getLocale()->get(
                "quiqqer/core",
                "console.tool.passwordreset.prompt.confirm",
                [
                    "username" => $username
                ]
            )
        );

        $confirm = strtolower(trim($this->readInput()));

        if ($confirm !== "y") {
            exit;
        }

        $this->writeLn(
            QUI::getLocale()->get(
                "quiqqer/core",
                "console.tool.passwordreset.prompt.confirm2",
                [
                    "username" => $username
                ]
            ),
            "yellow"
        );

        $confirm = strtolower(trim($this->readInput()));

        if ($confirm !== "y") {
            exit;
        }

        // Change the password!
        $password = Orthos::getPassword(random_int(8, 14));
        $User->setPassword($password, QUI::getUsers()->getSystemUser());

        $this->writeLn(
            QUI::getLocale()->get(
                "quiqqer/core",
                "console.tool.passwordreset.success",
                [
                    "password" => $password
                ]
            ),
            "green"
        );
    }

    /**
     * Output the help
     *
     * @param string $msg - [optional] extra text
     */
    public function help(string $msg = ''): never
    {
        $this->clearMsg();
        $this->getArguments();

        $this->writeLn(" Call");
        $this->writeLn(" ./console [--PARAMS]", 'red');
        $this->writeLn(" ./console [group:tool]", 'orange');
        $this->writeLn(" ./console [group] [tool]", 'orange');

        $this->clearMsg();
        $this->writeLn();
        $this->writeLn(" Optional arguments");
        $this->writeLn(" --help			This help text");

        $this->writeLn(" --username		Username", 'red');
        $this->writeLn(" --password		Password to login", 'red');

        $this->writeLn(" --listtools		Lists all available tools, including those that require a login");

        $this->writeLn();
        $this->writeLn();
        $this->writeLn();

        $this->displaySystemTools();

        $Climate = new CLImate();
        $Climate->white()->out("Command Groups");
        $Climate->white()->out("-------------");
        $Climate->white()->out('');

        $groups = array_keys($this->groupedTools);
        $Climate->white()->out(implode(', ', $groups));

        $this->writeLn($msg);
        exit;
    }

    /**
     * Display the list of the system tool
     */
    public function displaySystemTools()
    {
        $systemTools = $this->systemTools;

        $Climate = new CLImate();
        $Climate->blue()->out("Available System-Tools");
        $Climate->blue()->out("=============================================================");

        $data = [
            ['           Command', 'Description'],
            ['           -------', '-----------'],
            ['', '']
        ];

        foreach ($systemTools as $tool) {
            $Tool = $this->get($tool);

            $name = $tool;
            $description = QUI::getLocale()->get('quiqqer/core', 'console.systemtool.' . $tool);

            if ($Tool instanceof QUI\System\Console\Tool) {
                $name = $Tool->getName();
                $description = $Tool->getDescription();
            }

            /* @var $Tool Console\Tool */
            $data[] = [
                "\033[" . $this->colors['green'] . "m" . $name . "\033[0m",
                $description
            ];
        }

        $Climate->out('');
        $Climate->columns($data);
        $Climate->out('');
    }

    protected function displayToolsForGroups($group)
    {
        if (empty($this->groupedTools[$group])) {
            $this->writeLn('No tools found!', 'red');

            return;
        }

        $tools = $this->groupedTools[$group];

        $Climate = new CLImate();
        $Climate->blue()->out("Available Tools for " . $group);
        $Climate->blue()->out("=============================================================");

        $data = [
            ['           Short Command', 'Command', 'Description'],
            ['-------------', '-------', '-----------'],
            ['', '']
        ];

        foreach ($tools as $Tool) {
            /* @var $Tool Console\Tool */
            $name = $Tool->getName();
            $description = $Tool->getDescription();

            $parts = explode(':', $name);
            $command = ':' . $parts[1];


            $data[] = [
                "\033[" . $this->colors['green'] . "m" . $command . "\033[0m",
                $name,
                trim($description)
            ];
        }

        $Climate->out('');
        $Climate->columns($data);
        $Climate->out('');
    }

    /**
     * Execute the authentication
     * @throws \QUI\Users\Exception|\QUI\Database\Exception
     */
    protected function authenticate()
    {
        $authenticators = QUI\Users\Auth\Handler::getInstance()->getGlobalAuthenticators();

        if ($this->getArgument('u')) {
            $this->setArgument('username', $this->getArgument('u'));
        }

        if ($this->getArgument('p')) {
            $this->setArgument('password', $this->getArgument('p'));
        }

        foreach ($authenticators as $authenticator) {
            /* @var $Authenticator QUI\Users\AbstractAuthenticator */
            $Authenticator = new $authenticator('');

            if (!$Authenticator->isCLICompatible()) {
                continue;
            }

            $Authenticator->cliAuthentication($this);

            if (is_null($this->User)) {
                $this->setArgument('username', $Authenticator->getUser()->getName());
                $this->User = $Authenticator->getUser();
            }
        }

        if (!QUI::getUsers()->isUser($this->User)) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.login.fail'],
                401
            );
        }

        if (QUI::getUsers()->isNobodyUser($this->User)) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.login.fail'],
                401
            );
        }

        /* @var $User QUI\Users\User */
        $User = $this->User;
        $authenticators = $User->getAuthenticators();

        foreach ($authenticators as $Authenticator) {
            if ($Authenticator->isCLICompatible()) {
                $Authenticator->cliAuthentication($this);
            }
        }

        // login
        $Users = QUI::getUsers();
        $userAgent = '';

        $Session = QUI::getSession();
        $Session->set('auth', 1);
        $Session->set('secHash', $Users->getSecHash());
        $Session->set('uid', $User->getUUID());
        $Session->set('inAuthentication', true);

        QUI::getUsers()->login();

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
        }

        QUI::getDataBase()->update(
            $Users->table(),
            [
                'lastvisit' => time(),
                'user_agent' => $userAgent,
                'secHash' => $Users->getSecHash()
            ],
            ['uuid' => $User->getUUID()]
        );
    }

    /**
     * List all tools in the shell for selection
     */
    public function readToolFromShell()
    {
        $this->clearMsg();

        $Climate = new CLImate();
        $Climate->cyan()->out("Available Tools");
        $Climate->cyan()->out("===============");

        // build tools
        $tools = $this->get(true);
        ksort($tools);

        $data = [
            ['           Command', 'Description'],
            ['           -------', '-----------'],
            ['', '']
        ];

        foreach ($tools as $Tool) {
            /* @var $Tool Console\Tool */
            $data[] = [
                "\033[" . $this->colors['green'] . "m" . $Tool->getName() . "\033[0m",
                $Tool->getDescription()
            ];
        }

        $Climate->out('');
        $Climate->columns($data);

        $Climate->out('');
        $Climate->out('Please select a tool from the list');
        $Climate->inline("Tool: ");

        $tool = $this->readInput();
        $Exec = false;

        if ($tool === 'exit' || !$tool) {
            $this->writeLn();

            return;
        }

        if (isset($this->tools[$tool])) {
            $Exec = $this->tools[$tool];
        }

        if ($Exec) {
            /* @var $Exec Console\Tool */

            try {
                $Exec->execute();
            } catch (QUI\Exception $Exception) {
                Log::addAlert($Exception->getMessage(), [
                    'type' => 'cron',
                    'tool' => $tool
                ]);

                $this->writeLn($Exception->getMessage(), 'red');
                $this->writeLn();

                return;
            }
        } else {
            $this->writeLn('Tool not found!', 'red');
            $this->clearMsg();
        }

        @ob_end_clean();

        $this->writeLn('Would you like any other steps to do?');

        $this->readToolFromShell();
    }

    /**
     * Start the console, if a tool is selected, execute the tool
     */
    public function start()
    {
        if (!$this->getArgument('tool')) {
            return;
        }

        if ($Tool = $this->get($this->getArgument('tool'))) {
            try {
                if (is_array($Tool)) {
                    throw new QUI\Exception('Tool not found', 404);
                }

                if ($this->getArgument('help')) {
                    $Tool->outputHelp();

                    return;
                }

                $Tool->execute();
            } catch (QUI\Exception $Exception) {
                $this->writeLn($Exception->getMessage(), 'red');
                $this->writeLn();
            }

            return;
        }

        $this->writeLn('Tool not found', 'red');
        $this->writeLn();
    }
}
