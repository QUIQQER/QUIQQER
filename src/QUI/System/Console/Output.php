<?php

/**
 * This File contains QUI\Composer\ArrayOutput
 */

namespace QUI\System\Console;

use Exception;
use QUI\Events\Event;
use QUI\System\Log;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

use function json_encode;
use function strpos;

use const PHP_EOL;

/**
 * Class ArrayOutput
 *
 * @package QUI\Composer
 */
class Output extends \Symfony\Component\Console\Output\Output
{
    /**
     * @var string[] $lines - Contains all lines of the output
     */
    protected array $lines = [];

    /**
     * @var string $curLine - The current line that is written.
     */
    protected string $curLine = "";

    /**
     * Represents a collection of events.
     *
     * This class provides methods to manipulate a collection of events,
     * such as adding events, removing events, and getting the total count of events.
     */
    public Event $Events;

    /**
     * ArrayOutput constructor.
     * @param int $verbosity
     * @param bool $decorated
     * @param OutputFormatterInterface|null $formatter
     */
    public function __construct(?int $verbosity = self::VERBOSITY_NORMAL, bool $decorated = false, ?\Symfony\Component\Console\Formatter\OutputFormatterInterface $formatter = null)
    {
        parent::__construct($verbosity, $decorated, $formatter);
        $this->Events = new Event();
    }

    /**
     * Write a message into the output-
     * @param string $message - The message that should be written
     * @param bool $newline - true, if the current line should be finished.
     */
    protected function doWrite($message, $newline): void
    {
        $this->curLine .= $message;

        if (strpos(json_encode($message), PHP_EOL) > 0 || strpos(json_encode($message), '\b') > 0) {
            $newline = true;
        }

        if (!$newline) {
            return;
        }


        $this->lines[] = $this->curLine;
        $this->curLine = '';

        try {
            $this->Events->fireEvent('write', [$message]);
        } catch (Exception $e) {
            Log::addDebug($e->getMessage());
        }
    }

    /**
     * Returns all lines of the output in an array
     * @return string[]
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    /**
     * Clears all lines of the output
     */
    public function clearLines(): void
    {
        $this->lines = [];
    }
}
