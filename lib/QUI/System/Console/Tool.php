<?php

/**
 * This file contains System_Console_Tool
 */

namespace QUI\System\Console;

use League\CLImate\CLImate;
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
    protected $paramsList = [];

    /**
     * Console parameter, values of the parameter / arguments
     *
     * @var array
     */
    protected $params;

    /**
     * @var array
     */
    protected $examples = [];

    /**
     * @var bool
     */
    protected $systemTool = true;

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
        $this->paramsList[$name] = [
            'param'       => $name,
            'description' => $description,
            'short'       => $short,
            'optional'    => (bool)$optional
        ];

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
     * @param $example
     */
    public function addExample($example)
    {
        $this->examples[] = $example;
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

        if (\count($this->examples)) {
            $this->writeLn('Examples:', 'brown');
            $this->resetColor();

            foreach ($this->examples as $example) {
                $this->writeLn('- '.$example);
            }

            $this->writeLn();
        }

        $this->writeLn('Arguments:', 'brown');

        if (empty($this->paramsList)) {
            $this->resetColor();
            $this->writeLn('No arguments');
            $this->writeLn();
            $this->writeLn();

            return $this;
        }


        $this->writeLn();

        $Climate = new CLImate();
        $data    = [];

        foreach ($this->paramsList as $param) {
            $argv        = '--'.\ltrim($param['param'], '-');
            $description = '';

            if (isset($param['short']) && !empty($param['short'])) {
                $argv .= ' (-'.\ltrim($param['short'], '-').')';
            }

            if (isset($param['description']) && !empty($param['description'])) {
                $description .= $param['description'];
            }

            if (isset($param['optional']) && !empty($param['optional'])) {
                $description .= ' (optional)';
            }

            $data[] = [$argv, $description];
        }

        $Climate->columns($data);
        $Climate->out('');

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

        $name = \ltrim($name, '-');

        if (isset($this->params[$name])) {
            return $this->params[$name];
        }

        if (isset($this->params['--'.$name])) {
            return $this->params['--'.$name];
        }

        // short argument?
        foreach ($this->paramsList as $entry) {
            if ($entry['short'] == $name
                && isset($this->params[$entry['param']])
            ) {
                return $this->params[$entry['param']];
            }
        }

        $paramData = [];

        if (isset($this->paramsList[$name])) {
            $paramData = $this->paramsList[$name];
        }

        // if cli, read user input
        if (\php_sapi_name() == 'cli'
            && (isset($paramData['optional']) && !$paramData['optional'])
        ) {
            $this->writeLn('');
            $this->writeLn('Missing Argument', 'brown');

            $this->writeLn('');
            $this->write('--'.$name.': ', 'green');

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
     * Execute the Tool
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

    /**
     * Is this tool a system tool?
     * system tools can be executed without a user
     *
     * @return bool
     */
    public function isSystemTool()
    {
        return $this->systemTool;
    }
}
