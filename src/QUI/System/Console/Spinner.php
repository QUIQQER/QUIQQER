<?php

namespace QUI\System\Console;

use function chr;
use function posix_kill;
use function sprintf;
use function usleep;

use const SIGTERM;

class Spinner
{
    const DOTS = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];

    const DOTS2 = ['⣾', '⣽', '⣻', '⢿', '⡿', '⣟', '⣯', '⣷'];

    const DOTS3 = ['⠋', '⠙', '⠚', '⠞', '⠖', '⠦', '⠴', '⠲', '⠳', '⠓'];

    const DOTS4 = ['⠄', '⠆', '⠇', '⠋', '⠙', '⠸', '⠰', '⠠', '⠰', '⠸', '⠙', '⠋', '⠇', '⠆'];

    const DOTS5 = ['⠋', '⠙', '⠚', '⠒', '⠂', '⠂', '⠒', '⠲', '⠴', '⠦', '⠖', '⠒', '⠐', '⠐', '⠒', '⠓', '⠋'];

    const DOTS6 = [
        '⠁',
        '⠉',
        '⠙',
        '⠚',
        '⠒',
        '⠂',
        '⠂',
        '⠒',
        '⠲',
        '⠴',
        '⠤',
        '⠄',
        '⠄',
        '⠤',
        '⠴',
        '⠲',
        '⠒',
        '⠂',
        '⠂',
        '⠒',
        '⠚',
        '⠙',
        '⠉',
        '⠁'
    ];

    const DOTS7 = [
        '⠈',
        '⠉',
        '⠋',
        '⠓',
        '⠒',
        '⠐',
        '⠐',
        '⠒',
        '⠖',
        '⠦',
        '⠤',
        '⠠',
        '⠠',
        '⠤',
        '⠦',
        '⠖',
        '⠒',
        '⠐',
        '⠐',
        '⠒',
        '⠓',
        '⠋',
        '⠉',
        '⠈'
    ];

    const DOTS8 = [
        '⠁',
        '⠁',
        '⠉',
        '⠙',
        '⠚',
        '⠒',
        '⠂',
        '⠂',
        '⠒',
        '⠲',
        '⠴',
        '⠤',
        '⠄',
        '⠄',
        '⠤',
        '⠠',
        '⠠',
        '⠤',
        '⠦',
        '⠖',
        '⠒',
        '⠐',
        '⠐',
        '⠒',
        '⠓',
        '⠋',
        '⠉',
        '⠈',
        '⠈'
    ];

    const DOTS9 = ['⢹', '⢺', '⢼', '⣸', '⣇', '⡧', '⡗', '⡏'];

    const DOTS10 = ['⢄', '⢂', '⢁', '⡁', '⡈', '⡐', '⡠'];

    const DOTS11 = ['⠁', '⠂', '⠄', '⡀', '⢀', '⠠', '⠐', '⠈'];

    const DOTS12 = [
        '⢀⠀',
        '⡀⠀',
        '⠄⠀',
        '⢂⠀',
        '⡂⠀',
        '⠅⠀',
        '⢃⠀',
        '⡃⠀',
        '⠍⠀',
        '⢋⠀',
        '⡋⠀',
        '⠍⠁',
        '⢋⠁',
        '⡋⠁',
        '⠍⠉',
        '⠋⠉',
        '⠋⠉',
        '⠉⠙',
        '⠉⠙',
        '⠉⠩',
        '⠈⢙',
        '⠈⡙',
        '⢈⠩',
        '⡀⢙',
        '⠄⡙',
        '⢂⠩',
        '⡂⢘',
        '⠅⡘',
        '⢃⠨',
        '⡃⢐',
        '⠍⡐',
        '⢋⠠',
        '⡋⢀',
        '⠍⡁',
        '⢋⠁',
        '⡋⠁',
        '⠍⠉',
        '⠋⠉',
        '⠋⠉',
        '⠉⠙',
        '⠉⠙',
        '⠉⠩',
        '⠈⢙',
        '⠈⡙',
        '⠈⠩',
        '⠀⢙',
        '⠀⡙',
        '⠀⠩',
        '⠀⢘',
        '⠀⡘',
        '⠀⠨',
        '⠀⢐',
        '⠀⡐',
        '⠀⠠',
        '⠀⢀',
        '⠀⡀'
    ];

    const LINE = ['-', '\\', '|', '/'];

    const LINE2 = ['⠂', '-', '–', '—', '–', '-'];

    const PIPE = ['┤', '┘', '┴', '└', '├', '┌', '┬', '┐'];

    const SIMPLE_DOTS = [
        '.  ',
        '.. ',
        '...',
        '   '
    ];

    const SIMPLE_DOTS_SCROLLING = [
        '.  ',
        '.. ',
        '...',
        ' ..',
        '  .',
        '   '
    ];

    const STAR = ['✶', '✸', '✹', '✺', '✹', '✷'];

    const STAR2 = ['+', 'x', '*'];

    const FLIP = ['_', '_', '_', '-', '`', '`', '\'', '´', '-', '_', '_', '_'];

    const HAMBURGER = ['☱', '☲', '☴'];

    const GROW_VERTICAL = ['▁', '▃', '▄', '▅', '▆', '▇', '▆', '▅', '▄', '▃'];

    const GROW_HORIZONTAL = ['▏', '▎', '▍', '▌', '▋', '▊', '▉', '▊', '▋', '▌', '▍', '▎'];

    const BALOON = [' ', '.', 'o', 'O', '@', '*', ' '];

    const BALOON2 = ['.', 'o', 'O', '°', 'O', 'o', '.'];

    const NOISE = ['▓', '▒', '░'];

    const BOUNCE = ['⠁', '⠂', '⠄', '⠂'];

    const BOX_BOUNCE = ['▖', '▘', '▝', '▗'];

    const BOX_BOUNCE2 = ['▌', '▀', '▐', '▄'];

    const TRIANGLE = ['◢', '◣', '◤', '◥'];

    const ARC = ['◜', '◠', '◝', '◞', '◡', '◟'];

    const CIRCLE = ['◡', '⊙', '◠'];

    const SQUARE_CORNERS = ['◰', '◳', '◲', '◱'];

    const CIRCLE_QUARTERS = ['◴', '◷', '◶', '◵'];

    const CIRCLE_HALVES = ['◐', '◓', '◑', '◒'];

    const ARROW3 = [
        '▹▹▹▹▹',
        '▸▹▹▹▹',
        '▹▸▹▹▹',
        '▹▹▸▹▹',
        '▹▹▹▸▹',
        '▹▹▹▹▸'
    ];

    const BOUNCING_BAR = [
        '[    ]',
        '[=   ]',
        '[==  ]',
        '[=== ]',
        '[ ===]',
        '[  ==]',
        '[   =]',
        '[    ]',
        '[   =]',
        '[  ==]',
        '[ ===]',
        '[====]',
        '[=== ]',
        '[==  ]',
        '[=   ]'
    ];

    const BOUNCING_BALL = [
        '( ●    )',
        '(  ●   )',
        '(   ●  )',
        '(    ● )',
        '(     ●)',
        '(    ● )',
        '(   ●  )',
        '(  ●   )',
        '( ●    )',
        '(●     )'
    ];

    protected array $frames = [];

    protected int $length;

    protected int $current;

    /**
     * @var bool|mixed
     */
    protected mixed $useKeyboardInterrupts = true;

    protected int $childPid;

    protected string $message = '';

    protected string $blinkOff = "\e[?25l";

    protected string $blinkOn = "\e[?25h";

    protected string $clearLine = "\33[2K\r";

    /**
     * Spinner constructor.
     */
    public function __construct(array $frames, bool $use_keyboard_interrupts = true)
    {
        $this->frames = $frames;
        $this->length = count($this->frames);
        $this->current = 0;

        $this->useKeyboardInterrupts = $use_keyboard_interrupts;
    }

    /**
     * Runs the spinner
     *
     * @param string $message
     * @param $callback
     * @return mixed|void
     */
    public function run(string $message, $callback)
    {
        $this->message = $message;

        // Only start spinner if output is to STDOUT.
        // But not if output is redirected to a file
        if (!extension_loaded('pcntl') || !posix_isatty(STDOUT)) {
            return $callback();
        }

        $childPid = pcntl_fork();

        if ($childPid == -1) {
            $callback();
            return $callback();
        }

        if ($childPid) {
            // Parent process
            if ($this->useKeyboardInterrupts) {
                $this->keyboardInterrupts();
            }

            $this->childPid = $childPid;
            $res = $callback();
            posix_kill($childPid, SIGTERM);
            $this->resetTerminal();
            return $res;
        }

        // Child process
        $this->loopSpinnerFrames();
    }

    private function keyboardInterrupts(): void
    {
        // Keyboard interrupts. E.g. ctrl-c
        // Exit both parent and child process
        // They are both running the same code

        $keyboard_interrupts = function ($sigNo): never {
            posix_kill($this->childPid, SIGTERM);
            $this->resetTerminal();
            exit($sigNo);
        };

        pcntl_signal(SIGINT, $keyboard_interrupts);
        pcntl_signal(SIGTSTP, $keyboard_interrupts);
        pcntl_signal(SIGQUIT, $keyboard_interrupts);
        pcntl_async_signals(true);
    }

    private function resetTerminal(): void
    {
        echo $this->clearLine;
        echo $this->blinkOn;
    }

    private function loopSpinnerFrames(): void
    {
        echo $this->blinkOff;

        while (true) {
            $next = $this->next();

            echo chr(27) . '[0G';
            echo sprintf('%s %s', $this->frames[$next], $this->message);
            usleep(100000);
        }
    }

    protected function next(): int
    {
        $prev = $this->current;
        $this->current = $prev + 1;

        if ($this->current >= $this->length - 1) {
            $this->current = 0;
        }

        return $prev;
    }

    public function stop(): void
    {
        posix_kill($this->childPid, SIGTERM);
        $this->resetTerminal();
    }
}
