<?php

/**
 * This file contains \QUI\System\Console\Tools\RenameProject
 */

namespace QUI\System\Console\Tools;

use QUI;
use QUI\Exception;
use QUI\System\Console\Tool;
use QUI\Utils\Project;

use function str_replace;
use function trim;

/**
 * Class RenameProject
 */
class RenameProject extends Tool
{
    protected string $oldProjectName;

    protected string $newProjectName;

    protected QUI\Projects\Project $Project;

    /**
     * RenameProject constructor.
     */
    public function __construct()
    {
        $this->setName("quiqqer:rename-project");
        $this->setDescription(
            QUI::getLocale()->get("quiqqer/core", "console.tool.project.rename.description")
        );
    }

    /**
     * Executes the tools steps
     * @throws Exception
     */
    public function execute(): void
    {
        $this->writeLnLocale("console.tool.project.rename.prompt.projectname.info", "cyan");

        foreach (QUI::getProjectManager()->getProjects() as $projectName) {
            $this->writeLn(" * " . $projectName);
        }

        $this->writeLnLocale("console.tool.project.rename.prompt.projectname", "light_cyan");
        $this->oldProjectName = trim($this->readInput());

        try {
            $this->Project = QUI::getProject($this->oldProjectName);
        } catch (\Exception) {
            $this->writeLnLocale("console.tool.project.rename.project.not.found", "white");
            $this->writeLn();
            exit;
        }

        $this->writeLnLocale("console.tool.project.rename.prompt.new.name", "light_cyan");
        $this->newProjectName = trim($this->readInput());

        try {
            Project::validateProjectName($this->newProjectName);
        } catch (\Exception) {
            $this->writeLnLocale("console.tool.project.rename.validation.invalid.signs", "light_red");
            $this->resetColor();
            $this->newProjectName = $this->purgeProjectName($this->newProjectName);
            $this->writeLnLocale("console.tool.project.rename.validation.clear.name", "white");
            $this->write(" " . $this->newProjectName, "light_green");
            $this->writeLnLocale("console.tool.project.rename.prompt.continue.new.name", "light_cyan");
            if (trim(strtolower($this->readInput())) !== "y") {
                exit;
            }
        }


        QUI::getProjectManager()->rename($this->oldProjectName, $this->newProjectName);

        $this->writeLnLocale("console.tool.project.rename.finished.success");
        $this->writeLn();
    }

    /**
     * Prints a line to the output while using a locale variable of the 'quiqqer/core' group
     */
    protected function writeLnLocale(bool|string $locale, bool|string $color = false, bool|string $background = false): void
    {
        $text = QUI::getLocale()->get("quiqqer/core", $locale);

        $this->writeLn($text, $color, $background);
    }

    /**
     * Removes forbidden characters from the project name
     *
     * @param $name
     *
     * @return array|string|string[]
     */
    protected function purgeProjectName($name): array|string
    {
        $forbiddenCharacters = [
            '-',
            '.',
            ',',
            ':',
            ';',
            '#',
            '`',
            '!',
            '§',
            '$',
            '%',
            '&',
            '/',
            '?',
            '<',
            '>',
            '=',
            '\'',
            '"',
            ' '
        ];

        return str_replace($forbiddenCharacters, "", $name);
    }
}
