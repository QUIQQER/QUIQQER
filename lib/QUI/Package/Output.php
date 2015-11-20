<?php

/**
 * This file contains QUI\Package\Output
 */

namespace QUI\Package;

use QUI;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

/**
 * Output class for the Package-Manager
 * so he can use composer as symfony application
 *
 * @author www.namerobot.com (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 *
 * @event onOutput [ string $message ]
 */

class Output extends \Symfony\Component\Console\Output\Output
{
    /**
     * List of messages
     * @var array
     */
    protected $_messages = array();

    /**
     * current message string
     * @var string
     */
    protected $_message  = '';

    /**
     * Event Manager
     * @var \QUI\Events\Manager
     */
    public $Events;

    /**
     * Konstruktor
     *
     * @param integer $verbosity
     * @param string|boolean $decorated
     * @param OutputFormatterInterface $formatter
     */
    public function __construct($verbosity=self::VERBOSITY_NORMAL, $decorated=false, OutputFormatterInterface $formatter=null)
    {
        parent::__construct( $verbosity, $decorated, $formatter );

        $this->Events = new QUI\Events\Manager();
    }

    /**
     * Writes a message to the output.
     *
     * @param string  $message A message to write to the output
     * @param boolean $newline Whether to add a newline or not
     */
    public function doWrite($message, $newline)
    {
        $this->_message .= $message;

        $this->Events->fireEvent( 'output', array( $message, $newline ) );

        if ( !$newline ) {
            return;
        }

        $this->_messages[] = $this->_message;
        $this->_message    = '';
    }

    /**
     * @inheritdoc
     * @param array|string $messages
     * @param bool|false $newline
     * @param int $type
     */
    public function write($messages, $newline=false, $type=self::OUTPUT_RAW)
    {
        parent::write( $messages, $newline, $type );
    }

    /**
     * @inheritdoc
     * @param array|string $messages
     * @param int $type
     */
    public function writeln($messages, $type=self::OUTPUT_RAW)
    {
        $this->write($messages, true, $type);
    }

    /**
     * Return all messages
     * @return array
     */
    public function getMessages()
    {
        return $this->_messages;
    }

    /**
     * Clear all messages
     */
    public function clearMessages()
    {
        $this->_messages = array();
    }
}
