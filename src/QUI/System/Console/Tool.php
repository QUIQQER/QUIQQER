<?php

/**
 * This file contains System_Console_Tool
 */

namespace QUI\System\Console;

use League\CLImate\CLImate;
use QUI;

use function count;
use function ltrim;
use function php_sapi_name;

/**
 * Parent class for a console tool
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
abstract class Tool extends QUI\QDOM implements QUI\Interfaces\System\SystemOutput
{
    /**
     * Console parameter list, list of available parameter
     */
    protected array $paramsList = [];

    /**
     * Console parameter, values of the parameter / arguments
     */
    protected array $params;

    protected array $examples = [];

    protected bool $systemTool = true;

    /**
     * Set the name of the Tool
     */
    public function setName(string $name): self
    {
        $this->setAttribute('name', $name);

        return $this;
    }

    /**
     * Set the description of the Tool
     */
    public function setDescription(string $description): self
    {
        $this->setAttribute('description', $description);

        return $this;
    }

    /**
     * Set the help of the Tool
     *
     * @deprecated use addArgument for argument descriptions
     */
    public function setHelp(string $help): self
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
     * @param boolean|string $short - optional, shortcut
     * @param boolean $optional - optional, Argument is optional
     *
     * @return Tool
     */
    public function addArgument(
        string $name,
        string $description,
        bool|string $short = false,
        bool $optional = false
    ): self {
        $this->paramsList[$name] = [
            'param' => $name,
            'description' => $description,
            'short' => $short,
            'optional' => $optional
        ];

        return $this;
    }

    public function setArgument(string $name, bool|string $value): self
    {
        $this->params[$name] = $value;

        return $this;
    }

    /**
     * @param $example
     */
    public function addExample($example): void
    {
        $this->examples[] = $example;
    }

    /**
     * Return the name of the Tool
     */
    public function getName(): bool|string
    {
        return $this->getAttribute('name');
    }

    /**
     * Return the name of the Tool
     */
    public function getDescription(): bool|string
    {
        return $this->getAttribute('description');
    }

    /**
     * Output the tool help
     *
     * @return $this
     */
    public function outputHelp(): static
    {
        $this->writeLn($this->getAttribute('description'));
        $this->writeLn();

        $this->writeLn('Command:', 'brown');
        $this->writeLn($this->getAttribute('name'), 'purple');
        $this->writeLn();

        if (count($this->examples)) {
            $this->writeLn('Examples:', 'brown');
            $this->resetColor();

            foreach ($this->examples as $example) {
                $this->writeLn('- ' . $example);
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
        $data = [];

        foreach ($this->paramsList as $param) {
            $argv = '--' . ltrim($param['param'], '-');
            $description = '';

            if (!empty($param['short'])) {
                $argv .= ' (-' . ltrim($param['short'], '-') . ')';
            }

            if (!empty($param['description'])) {
                $description .= $param['description'];
            }

            if (!empty($param['optional'])) {
                $description .= ' (optional)';
            }

            $data[] = [$argv, $description];
        }

        $Climate->columns($data);
        $Climate->out('');

        return $this;
    }

    /**
     * Output a line to the parent
     *
     * @param string $msg - (optional) Message
     * @param boolean|string $color - (optional) Text color
     * @param boolean|string $bg - (optional) Background color
     */
    public function writeLn(string $msg = '', bool|string $color = false, bool|string $bg = false): void
    {
        if ($this->getAttribute('parent')) {
            $this->getAttribute('parent')->writeLn($msg, $color, $bg);
        }
    }

    /**
     * Reset the color
     */
    public function resetColor(): void
    {
        if ($this->getAttribute('parent')) {
            $this->getAttribute('parent')->clearMsg();
        }
    }

    /**
     * Return the value of an argument
     * If the argument is not set and the user are in the cli, then a user input is required (->readInput())
     *
     * An argument can look like: --argument, argument, -shortArgument
     */
    public function getArgument(string $name): bool|string
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
            if (
                $entry['short'] == $name
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
        if (
            php_sapi_name() == 'cli' &&
            (isset($paramData['optional']) && $paramData['optional'] === false)
        ) {
            $this->writeLn();
            $this->writeLn('Missing Argument', 'brown');

            $this->writeLn();
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
     * Alternative to ->message()
     *
     * @param string $msg - Message
     * @param boolean|string $color - optional, Text color
     * @param boolean|string $bg - optional, Background color
     */
    public function write(string $msg, bool|string $color = false, bool|string $bg = false): void
    {
        $this->message($msg, $color, $bg);
    }

    /**
     * Print a message
     *
     * @param string $msg - Message
     * @param boolean|string $color - optional, Text color
     * @param boolean|string $bg - optional, Background color
     */
    public function message(string $msg, bool|string $color = false, bool|string $bg = false): void
    {
        if ($this->getAttribute('parent')) {
            $this->getAttribute('parent')->message($msg, $color, $bg);
        }
    }

    /**
     * Read the input from the user
     */
    public function readInput(): string
    {
        if ($this->getAttribute('parent')) {
            return $this->getAttribute('parent')->readInput();
        }

        return '';
    }

    /**
     * Execute the Tool
     */
    public function execute(): void
    {
    }

    /**
     * Is this tool a system tool?
     * system tools can be executed without a user
     */
    public function isSystemTool(): bool
    {
        return $this->systemTool;
    }
}
