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
 * @licence For copyright and license information, please view the /README.md
 */
abstract class Tool extends QUI\QDOM
{
    /**
     * Console parameter list, list of available parameter
     *
     * @var array
     */
    protected $paramsList = array();

    /**
     * Console parameter, values of the parameter / arguments
     *
     * @var array
     */
    protected $params;

    /**
     * Set the name of the Tool
     *
     * @param string $name
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
     * @param string $description
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
     * @param string $help
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
     * @param string $name - Name of the argument
     * @param string $description - Description of the argument
     * @param string|boolean $short - optional, shortcut
     * @param boolean $optional - optional, Argument is optional
     *
     * @return Tool this
     */
    public function addArgument(
        $name,
        $description,
        $short = false,
        $optional = false
    ) {
        $this->paramsList[$name] = array(
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
     * @param string $name
     * @param string|boolean $value
     *
     * @return Tool this
     */
    public function setArgument($name, $value)
    {
        $this->params[$name] = $value;

        return $this;
    }

    /**
     * Return the name of the Tool
     *
     * @return string|boolean
     */
    public function getName()
    {
        return $this->getAttribute('name');
    }

    /**
     * Return the name of the Tool
     *
     * @return string|boolean
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

        foreach ($this->paramsList as $param) {
            $command = '--' . ltrim($param['param'], '-');
            $short   = '';

            if (isset($param['short']) && !empty($param['short'])) {
                $short = ' (-' . ltrim($param['short'], '-') . ')';
            }

            $strlen = strlen("{$command}{$short}");

            if ($strlen > $maxLen) {
                $maxLen = $strlen;
            }
        }

        // output
        foreach ($this->paramsList as $param) {
            $command = '--' . ltrim($param['param'], '-');
            $short   = '';

            if (isset($param['short']) && !empty($param['short'])) {
                $short = ' (-' . ltrim($param['short'], '-') . ')';
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
     * @param string
     *
     * @return string|bool
     */
    public function getArgument($name)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }

        $name = ltrim($name, '-');

        if (isset($this->params[$name])) {
            return $this->params[$name];
        }

        if (isset($this->params['--' . $name])) {
            return $this->params['--' . $name];
        }

        // short argument?
        foreach ($this->paramsList as $entry) {
            if ($entry['short'] == $name
                && isset($this->params[$entry['param']])
            ) {
                return $this->params[$entry['param']];
            }
        }

        $paramData = array();

        if (isset($this->paramsList[$name])) {
            $paramData = $this->paramsList[$name];
        }

        // if cli, read user input
        if (php_sapi_name() == 'cli'
            && (isset($paramData['optional']) && !$paramData['optional'])
        ) {
            $this->writeLn('');
            $this->writeLn('Missing Argument', 'brown');

            $this->writeLn('');
            $this->write('--' . $name . ': ', 'green');

            $this->resetColor();

            if (isset($this->paramsList[$name])) {
                $paramData = $this->paramsList[$name];

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
     * @param string $msg - (optional) Message
     * @param string|boolean $color - (optional) Text color
     * @param string|boolean $bg - (optional) Background color
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
     * @param string $msg - Message
     * @param string|boolean $color - optional, Text color
     * @param string|boolean $bg - optional, Background color
     */
    public function write($msg, $color = false, $bg = false)
    {
        $this->message($msg, $color, $bg);
    }

    /**
     * Print a message
     *
     * @param string $msg - Message
     * @param string|boolean $color - optional, Text color
     * @param string|boolean $bg - optional, Background color
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
     * @return string
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
