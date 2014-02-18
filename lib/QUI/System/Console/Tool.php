<?php

/**
 * This file contains System_Console_Tool
 */

namespace QUI\System\Console;

/**
 * Parent class for a console tool
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.system.console
 */

class Tool extends \QUI\QDOM
{
    /**
     * Console parameter
     * @var array
     */
    protected $_params;

    /**
     * Help String
     * @var String
     */
    protected $_help;

    /**
     * Set the name of the Tool
     * @param String $name
     */
    public function setName($name)
    {
        $this->setAttribute( 'name', $name );

        return $this;
    }

    /**
     * Set the description of the Tool
     * @param String $desc
     */
    public function setDescription($description)
    {
        $this->setAttribute( 'description', $description );

        return $this;
    }

    /**
     * Set the help of the Tool
     * @param String $desc
     */
    public function setHelp($help)
    {
        $this->setAttribute( 'help', $help );

        return $this;
    }

    /**
     * Set value of an argument
     *
     * @param String $name
     * @param String|Bool $value
     */
    public function setArgument($name, $value)
    {
        $this->_params[ $name ] = $value;

        return $this;
    }

    /**
     * Return the name of the Tool
     * @return String|Bool
     */
    public function getName()
    {
        return $this->getAttribute( 'name' );
    }

    /**
     * Return the name of the Tool
     * @return String|Bool
     */
    public function getDescription()
    {
        return $this->getAttribute( 'description' );
    }

    /**
     * Return the name of the Tool
     * @return String|Bool
     */
    public function getHelp()
    {
        return $this->getAttribute( 'help' );
    }

    /**
     * Return the value of an argument
     *
     * @param String
     */
    public function getArgument($name)
    {
        if ( isset( $this->_params[ $name ] ) ) {
            return $this->_params[ $name ];
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
     * @param String $msg	- Message
     * @param String $color - optional, Text color
     * @param String $bg	- optional, Background color
     */
    public function writeLn($msg, $color=false, $bg=false)
    {
        if ( $this->getAttribute( 'parent' ) ) {
            $this->getAttribute( 'parent' )->writeLn( $msg, $color, $bg );
        }
    }

    /**
     * Alternative to ->message()
     *
     * @param String $msg	- Message
     * @param String $color - optional, Text color
     * @param String $bg	- optional, Background color
     */
    public function write($msg, $color=false, $bg=false)
    {
        $this->message( $msg, $color, $bg );
    }

    /**
     * Print a message
     *
     * @param String $msg	- Message
     * @param String $color - optional, Text color
     * @param String $bg	- optional, Background color
     */
    public function message($msg, $color=false, $bg=false)
    {
        if ( $this->getAttribute( 'parent' ) ) {
            $this->getAttribute( 'parent' )->message( $msg, $color, $bg );
        }
    }

    /**
     * Read the input from the user
     *
     * @return String
     */
    public function readInput()
    {
        if ( $this->getAttribute( 'parent' ) ) {
            return $this->getAttribute( 'parent' )->readInput();
        }

        return '';
    }

    /**
     * Reset the color
     */
    public function resetColor()
    {
        if ( $this->getAttribute( 'parent' ) ) {
            $this->getAttribute( 'parent' )->clearMsg();
        }
    }
}
