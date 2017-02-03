<?php

/**
 * This file contains System_Console
 */

namespace QUI\System;

use QUI;
use QUI\Utils\Security\Orthos;

/**
 * The QUIQQER Console
 *
 * With the console you can start tools / crons in the shell
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @author  www.pcsg.de (Moritz Scholz)
 * @licence For copyright and license information, please view the /README.md
 *
 * @package com.pcsg.qui.system.console
 */
class Console
{
    /**
     * All available console tools
     *
     * @var array
     */
    private $tools = array();

    /**
     * Console parameter
     *
     * @var array
     */
    private $argv;

    /**
     * The current text color
     *
     * @var string
     */
    protected $current_color = false;

    /**
     * the current background color
     *
     * @var string
     */
    protected $current_bg = false;

    /**
     * All available text colors
     *
     * @var array
     */
    protected $colors = array(
        'black'        => '0;30',
        'dark_gray'    => '1;30',
        'blue'         => '0;34',
        'light_blue'   => '1;34',
        'green'        => '0;32',
        'light_green'  => '1;32',
        'cyan'         => '0;36',
        'light_cyan'   => '1;36',
        'red'          => '0;31',
        'light_red'    => '1;31',
        'purple'       => '0;35',
        'light_purple' => '1;35',
        'brown'        => '0;33',
        'yellow'       => '1;33',
        'light_gray'   => '0;37',
        'white'        => '1;37',
        'black_u'      => '4;30',
        'red_u'        => '4;31',
        'green_u'      => '4;32',
        'yellow_u'     => '4;33',
        'blue_u'       => '4;34',
        'purple_u'     => '4;35',
        'cyan_u'       => '4;36',
        'white_u'      => '4;37'
    );

    /**
     * All available background colors
     *
     * @var array
     */
    protected $bg = array(
        'black'      => '40',
        'red'        => '41',
        'green'      => '42',
        'yellow'     => '43',
        'blue'       => '44',
        'magenta'    => '45',
        'cyan'       => '46',
        'light_gray' => '47'
    );

    /**
     * constructor
     */
    public function __construct()
    {
        $this->title();

        if (!isset($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = '';
        }

        if (!isset($_SERVER['argv'])) {
            $this->writeLn("Cannot use Consoletools");
            exit;
        }

        $params = $this->readArgv();

        if (isset($params['--help']) && !isset($params['--tool'])) {
            $this->help();
            exit;
        }

        if (isset($params['-u']) && isset($params['-p'])) {
            $params['--username'] = $params['-u'];
            $params['--password'] = $params['-p'];
        }

        if (!isset($params['--username'])) {
            $this->writeLn("Please enter your username and password");
            $this->writeLn("Username: ", 'green');

            $params['--username'] = $this->readInput();

            $this->write("Password: ", 'green');
            $params['--password'] = QUI\Utils\System\Console::readPassword();
        }

        if (!isset($params['--password'])) {
            $this->writeLn("Password:", 'green');
            $params['--password'] = QUI\Utils\System\Console::readPassword();
        }

        try {
            $User = QUI::getUsers()->login(array(
                'username' => $params['--username'],
                'password' => $params['--password']
            ));
        } catch (QUI\Exception $Exception) {
            $this->writeLn($Exception->getMessage() . "\n\n", 'red');
            exit;
        }

        if (!$User->getId()) {
            $this->writeLn("Login incorrect\n\n", 'red');
            exit;
        }

        QUI::getSession()->set('uid', $User->getId());

        QUI\Permissions\Permission::setUser($User);

        if (!QUI\Permissions\Permission::hasPermission('quiqqer.system.console')) {
            $this->writeLn("Missing rights to use the console\n\n", 'red');
            $this->clearMsg();
            exit;
        }

        // Login
        $this->argv = $params;
        $this->read();

        if (isset($params['--listtools'])) {
            $this->title();
            $this->writeLn("Tools\n");

            $tools = $this->get(true);

            foreach ($tools as $tool => $obj) {
                $this->writeLn(" - " . $tool . "\n");
            }

            $this->writeLn("\n");
        }

        if (!isset($params['--tool']) && !isset($params['--listtools'])) {
            $this->readToolFromShell();
        }
    }

    /**
     * Read the argv params
     *
     * @return array
     */
    protected function readArgv()
    {
        // Vars löschen die Probleme bereiten können
        $_REQUEST = array();
        $_POST    = array();
        $_GET     = array();

        if (isset($_SERVER['argv'][0])) {
            unset($_SERVER['argv'][0]);
        }

        $params = array();

        // Parameter auslesen
        foreach ($_SERVER['argv'] as $argv) {
            if (strpos($argv, '=') !== false) {
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
     * List all tools in the shell for selection
     */
    public function readToolFromShell()
    {
        $this->clearMsg();
        $this->writeLn("Available Tools");

        $tools = $this->get(true);

        ksort($tools);

        foreach ($tools as $Tool) {
            /* @var $Tool Console\Tool */
            $this->writeLn(" - ");
            $this->write($Tool->getName(), 'green');

            $this->clearMsg();
            $this->write("\t\t");
            $this->write($Tool->getDescription());
        }

        $this->writeLn("");
        $this->writeLn("Please select a tool from the list");
        $this->writeLn("Tool: ");

        $tool = $this->readInput();
        $Exec = false;

        if ($tool == 'exit' || !$tool) {
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
                Log::addAlert($Exception->getMessage(), array(
                    'type' => 'cron',
                    'tool' => $tool
                ));

                $this->writeLn($Exception->getMessage(), 'red');
                $this->writeLn();

                return;
            }
        } else {
            $this->writeLn('Tool not found!', 'red');
            $this->clearMsg();
        }

        $this->writeLn('Would you like any other steps to do?');

        $this->readToolFromShell();
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

        if ($tool == true) {
            return $this->tools;
        }

        return false;
    }

    /**
     * Start the console, if a tool is selected, execute the tool
     */
    public function start()
    {
        if (!isset($this->argv['--tool'])) {
            return;
        }

        if ($Tool = $this->get($this->argv['--tool'])) {
            try {
                if (is_array($Tool) || !$Tool) {
                    throw new QUI\Exception('Tool not found', 404);
                }

                if (isset($this->argv['--help'])) {
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

    /**
     * Read all tools and include it
     */
    private function read()
    {
        // Standard Konsoletools
        $path  = LIB_DIR . 'QUI/System/Console/Tools/';
        $files = QUI\Utils\System\File::readDir($path, true);

        for ($i = 0, $len = count($files); $i < $len; $i++) {
            if (!file_exists($path . $files[$i])) {
                continue;
            }

            $this->includeClasses($files[$i], $path);
        }

        // look at console tools at plugins
        $PackageManager = QUI::getPackageManager();
        $plugins        = $PackageManager->getInstalled();

        $tools = array();

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
        $projects       = $ProjectManager->getProjects();

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

            foreach ($this->argv as $key => $value) {
                $Tool->setArgument($key, $value);
            }

            $this->tools[$Tool->getName()] = $Tool;
        }
    }

    /**
     * Include the tool class
     *
     * @param string $file
     * @param string $dir
     *
     * @throws QUI\Exception
     */
    protected function includeClasses($file, $dir)
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

        foreach ($this->argv as $key => $value) {
            $Tool->setArgument($key, $value);
        }

        $this->tools[$Tool->getName()] = $Tool;
    }

    /**
     * Output the help
     *
     * @param string $msg - [optional] extra text
     */
    public function help($msg = '')
    {
        $this->title();
        $this->clearMsg();

        $this->writeLn();
        $this->writeLn(" Call");
        $this->writeLn(
            " php quiqqer.php --username=[USERNAME] --password=[PASSWORD] --tool=[TOOLNAME] [--PARAMS]",
            'red'
        );

        $this->clearMsg();
        $this->writeLn("");
        $this->writeLn(" Required arguments");

        $this->writeLn(" --username		Username", 'red');
        $this->writeLn(" --password		Password to login", 'red');

        $this->clearMsg();
        $this->writeLn("");
        $this->writeLn(" Optional arguments");
        $this->writeLn(" --help			This help text");
        $this->writeLn(" --listtools		Lists the available console tools");
        $this->writeLn(" 			Only with the correct login");

        $this->writeLn($msg);
        exit;
    }

    /**
     * QUIQQER Console title
     * output the main quiqqer console info
     */
    public function title()
    {
        $params = $this->readArgv();

        if (isset($params['--noLogo'])) {
            return;
        }

        $str
            = '
         _______          _________ _______  _______  _______  _______
        (  ___  )|\     /|\__   __/(  ___  )(  ___  )(  ____ \(  ____ )
        | (   ) || )   ( |   ) (   | (   ) || (   ) || (    \/| (    )|
        | |   | || |   | |   | |   | |   | || |   | || (__    | (____)|
        | |   | || |   | |   | |   | |   | || |   | ||  __)   |     __)
        | | /\| || |   | |   | |   | | /\| || | /\| || (      | (\ (
        | (_\ \ || (___) |___) (___| (_\ \ || (_\ \ || (____/\| ) \ \__
        (____\/_)(_______)\_______/(____\/_)(____\/_)(_______/|/   \__/


            Welcome to QUIQQER.

        ';

        $this->message($str, 'green', 'white');
        $this->clearMsg();
    }

    /**
     * clear the console (all colors)
     */
    public function clear()
    {
        array_map(
            create_function('$a', 'print chr($a);'),
            array(27, 91, 72, 27, 91, 50, 74)
        );
    }

    /**
     * Read the input from the user -> STDIN
     *
     * @return string
     */
    public function readInput()
    {
        return trim(fgets(STDIN));
    }

    /**
     * Write a new line
     *
     * @param string $msg - (optional) the printed message
     * @param string|boolean $color - (optional) textcolor
     * @param string|boolean $bg - (optional) background color
     */
    public function writeLn($msg = '', $color = false, $bg = false)
    {
        $this->message("\n" . $msg, $color, $bg);
    }

    /**
     * alternative for message()
     *
     * @param string $msg - Message to output
     * @param string|boolean $color - (optional) textcolor
     * @param string|boolean $bg - (optional) background color
     */
    public function write($msg, $color = false, $bg = false)
    {
        $this->message($msg, $color, $bg);
    }

    /**
     * Output a message
     *
     * @param string $msg - Message to output
     * @param string|boolean $color - (optional) textcolor
     * @param string|boolean $bg - (optional) background color
     */
    public function message($msg, $color = false, $bg = false)
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
        $this->current_bg    = false;

        echo "\033[0m";
    }
}
