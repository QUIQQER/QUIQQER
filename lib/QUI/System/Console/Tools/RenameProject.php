<?php

/**
 * This file contains \QUI\System\Console\Tools\RenameProject
 */

namespace QUI\System\Console\Tools;

use QUI;
use QUI\System\Console\Tool;
use QUI\Utils\Project;

/**
 * Class RenameProject
 */
class RenameProject extends Tool
{
    protected $oldProjectName;
    protected $newProjectName;
    protected $Project;

    /**
     * RenameProject constructor.
     */
    public function __construct()
    {
        $this->setName("quiqqer:rename-project");
        $this->setDescription(
            QUI::getLocale()->get("quiqqer/quiqqer", "console.tool.project.rename.description")
        );
    }

    /**
     * Executes the tools steps
     */
    public function execute()
    {
        $this->writeLnLocale("console.tool.project.rename.prompt.projectname.info", "cyan");

        foreach (QUI::getProjectManager()->getProjects() as $projectName) {
            $this->writeLn(" * " . $projectName);
        }

        $this->writeLnLocale("console.tool.project.rename.prompt.projectname", "light_cyan");
        $this->oldProjectName = \trim($this->readInput());

        try {
            $this->Project = QUI::getProject($this->oldProjectName);
        } catch (\Exception $Exception) {
            $this->writeLnLocale("console.tool.project.rename.project.not.found", "white");
            $this->writeLn("");
            exit;
        }

        $this->writeLnLocale("console.tool.project.rename.prompt.new.name", "light_cyan");
        $this->newProjectName = \trim($this->readInput());

        try {
            Project::validateProjectName($this->newProjectName);
        } catch (\Exception) {
            $this->writeLnLocale("console.tool.project.rename.validation.invalid.signs", "light_red");
            $this->resetColor();
            $this->newProjectName = $this->purgeProjectName($this->newProjectName);
            $this->writeLnLocale("console.tool.project.rename.validation.clear.name", "white");
            $this->write(" " . $this->newProjectName, "light_green");
            $this->writeLnLocale("console.tool.project.rename.prompt.continue.new.name", "light_cyan");
            if (trim(strtolower($this->readInput())) != "y") {
                exit;
            }
        }


        QUI::getProjectManager()->rename($this->oldProjectName, $this->newProjectName);

        $this->writeLnLocale("console.tool.project.rename.finished.success");
        $this->writeLn("");
    }

    /**
     * Prints a line to the output while using a locale variable of the 'quiqqer/quiqqer' group
     *
     * @param $locale
     * @param bool $color
     * @param bool $background
     */
    protected function writeLnLocale($locale, $color = false, $background = false)
    {
        $text = QUI::getLocale()->get("quiqqer/quiqqer", $locale);

        $this->writeLn($text, $color, $background);
    }

    /**
     * Removes forbidden characters from the project name
     *
     * @param $name
     *
     * @return mixed
     */
    protected function purgeProjectName($name)
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

        return \str_replace($forbiddenCharacters, "", $name);
    }
}
