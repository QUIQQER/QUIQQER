<?php

/**
 * This file contains System_Console_Tool
 */

namespace QUI\System\Console;

use QUI;

/**
 * Parent class for a console tool
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.system.console
 */
abstract class Tool extends QUI\QDOM
{
    /**
     * Console parameter list, list of available parameter
     *
     * @var array
     */
    protected $_paramsList = array();

    /**
     * Console parameter, values of the parameter / arguments
     *
     * @var array
     */
    protected $_params;

    /**
     * Set the name of the Tool
     *
     * @param String $name
     *
     * @return Tool this
     */
    public function setName($name)
    {
        $this->setAttribute('name', $name);

        return $this;
    }

    /**
     * Set the description of the Tool
     *
     * @param String $description
     *
     * @return Tool this
     */
    public function setDescription($description)
    {
        $this->setAttribute('description', $description);

        return $this;
    }

    /**
     * Set the help of the Tool
     *
     * @param String $help
     *
     * @return Tool this
     * @deprecated use addArgument for argument desriptions
     */
    public function setHelp($help)
    {
        $this->setAttribute('help', $help);

        return $this;
    }

    /**
     * Add an Argument, with its description
     *
     * Not allowed arguments / Parameter are:
     * help, tool, listtools, u, p, username, password
     *
     * @param String      $name        - Name of the argument
     * @param             $description - Description of the argument
     * @param String|Bool $short       - optional, shortcut
     * @param Bool        $optional    - optional, Argument is optional
     *
     * @return Tool this
     */
    public function addArgument(
        $name,
        $description,
        $short = false,
        $optional = false
    ) {
        $this->_paramsList[$name] = array(
            'param'       => $name,
            'description' => $description,
            'short'       => $short,
            'optional'    => (bool)$optional
        );

        return $this;
    }

    /**
     * Set value of an argument
     *
     * @param String      $name
     * @param String|Bool $value
     *
     * @return Tool this
     */
    public function setArgument($name, $value)
    {
        $this->_params[$name] = $value;

        return $this;
    }

    /**
     * Return the name of the Tool
     *
     * @return String|Bool
     */
    public function getName()
    {
        return $this->getAttribute('name');
    }

    /**
     * Return the name of the Tool
     *
     * @return String|Bool
     */
    public function getDescription()
    {
        return $this->getAttribute('description');
    }

    /**
     * Output the tool help
     *
     * @return $this
     */
    public function outputHelp()
    {
        $this->writeLn($this->getAttribute('description'));
        $this->writeLn();

        $this->writeLn('Command:', 'brown');
        $this->writeLn($this->getAttribute('name'), 'purple');
        $this->writeLn();

        $this->writeLn('Arguments:', 'brown');
        $this->writeLn();

        // space calc
        $maxLen = 0;

        foreach ($this->_paramsList as $param) {

            $command = '--'.ltrim($param['param'], '-');
            $short = '';

            if (isset($param['short']) && !empty($param['short'])) {
                $short = ' (-'.ltrim($param['short'], '-').')';
            }

            $strlen = strlen("{$command}{$short}");

            if ($strlen > $maxLen) {
                $maxLen = $strlen;
            }
        }

        // output
        foreach ($this->_paramsList as $param) {

            $command = '--'.ltrim($param['param'], '-');
            $short = '';

            if (isset($param['short']) && !empty($param['short'])) {
                $short = ' (-'.ltrim($param['short'], '-').')';
            }

            $this->write($command, 'green');

            $this->resetColor();
            $this->write("{$short}");

            $this->write(
                str_repeat(
                    ' ',
                    $maxLen - strlen("{$command}{$short}") + 4
                )
            ); // spaces to the description

            $this->write("{$param['description']}");

            if ($param['optional']) {
                $this->write(" (optional)", "dark_gray");
            }

            $this->resetColor();
            $this->writeLn();
        }

        $this->writeLn();

        return $this;
    }

    /**
     * Return the value of an argument
     * If the argument is not set and the user are in the cli, then a user input is required (->readInput())
     *
     * An argument can looks like: --argument, argument, -shortArgument
     *
     * @param String
     *
     * @return string|bool
     */
    public function getArgument($name)
    {
        if (isset($this->_params[$name])) {
            return $this->_params[$name];
        }

        $name = ltrim($name, '-');

        if (isset($this->_params[$name])) {
            return $this->_params[$name];
        }

        if (isset($this->_params['--'.$name])) {
            return $this->_params['--'.$name];
        }

        // short argument?
        foreach ($this->_paramsList as $entry) {
            if ($entry['short'] == $name
                && isset($this->_params[$entry['param']])
            ) {
                return $this->_params[$entry['param']];
            }
        }

        $paramData = array();

        if (isset($this->_paramsList[$name])) {
            $paramData = $this->_paramsList[$name];
        }

        // if cli, read user input
        if (php_sapi_name() == 'cli'
            && (isset($paramData['optional']) && !$paramData['optional'])
        ) {

            $this->writeLn('');
            $this->writeLn('Missing Argument', 'brown');

            $this->writeLn('');
            $this->write('--'.$name.': ', 'green');

            $this->resetColor();

            if (isset($this->_paramsList[$name])) {
                $paramData = $this->_paramsList[$name];

                $this->write($paramData['description']);
                $this->writeLn();
            }

            $this->writeLn();
            $this->write('Please enter the value:');

            return $this->readInput();
        }

        return false;
    }

    /**
     * Execut the Tool
     */
    public function execute()
    {

    }

    /**
     * Output a line to the parent
     *
     * @param String      $msg   - (optional) Message
     * @param String|bool $color - (optional) Text color
     * @param String|bool $bg    - (optional) Background color
     */
    public function writeLn($msg = '', $color = false, $bg = false)
    {
        if ($this->getAttribute('parent')) {
            $this->getAttribute('parent')->writeLn($msg, $color, $bg);
        }
    }

    /**
     * Alternative to ->message()
     *
     * @param String      $msg   - Message
     * @param String|bool $color - optional, Text color
     * @param String|bool $bg    - optional, Background color
     */
    public function write($msg, $color = false, $bg = false)
    {
        $this->message($msg, $color, $bg);
    }

    /**
     * Print a message
     *
     * @param String      $msg   - Message
     * @param String|bool $color - optional, Text color
     * @param String|bool $bg    - optional, Background color
     */
    public function message($msg, $color = false, $bg = false)
    {
        if ($this->getAttribute('parent')) {
            $this->getAttribute('parent')->message($msg, $color, $bg);
        }
    }

    /**
     * Read the input from the user
     *
     * @return String
     */
    public function readInput()
    {
        if ($this->getAttribute('parent')) {
            return $this->getAttribute('parent')->readInput();
        }

        return '';
    }

    /**
     * Reset the color
     */
    public function resetColor()
    {
        if ($this->getAttribute('parent')) {
            $this->getAttribute('parent')->clearMsg();
        }
    }
}
