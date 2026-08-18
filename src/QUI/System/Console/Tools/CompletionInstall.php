<?php

namespace QUI\System\Console\Tools;

use QUI;
use QUI\System\Console\Completion\Installer;

use function getenv;
use function is_string;

class CompletionInstall extends QUI\System\Console\Tool
{
    public function __construct()
    {
        $this
            ->setName('completion:install')
            ->setDescription('Install shell completion for the current user.')
            ->addArgument('shell', 'Shell to install completion for (bash, zsh or fish).', false, true);
    }

    /**
     * @throws QUI\Exception
     */
    public function execute(): void
    {
        $homeDirectory = getenv('HOME');

        if (!is_string($homeDirectory) || $homeDirectory === '') {
            throw new QUI\Exception('Could not determine the current user home directory.');
        }

        $shell = $this->getArgument('shell');

        if (!is_string($shell) || $shell === '') {
            $shell = getenv('SHELL');
        }

        if (!is_string($shell) || $shell === '') {
            throw new QUI\Exception('Could not detect the current shell. Please use --shell=bash, zsh or fish.');
        }

        $configDirectory = getenv('XDG_CONFIG_HOME');
        $Installer = new Installer(
            $homeDirectory,
            is_string($configDirectory) && $configDirectory !== '' ? $configDirectory : null
        );
        $result = $Installer->install($shell);

        $this->writeLn('QUIQQER console completion installed successfully.', 'green');
        $this->writeLn('Completion file: ' . $result['completionFile']);

        if ($result['shellConfigFile']) {
            $this->writeLn('Shell configuration: ' . $result['shellConfigFile']);
        }

        $this->writeLn('Restart your shell to activate completion.');
    }
}
