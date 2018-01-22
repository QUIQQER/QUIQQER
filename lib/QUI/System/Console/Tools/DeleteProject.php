<?php

/**
 * This file contains QUI\System\Console\Tools\DeleteProject
 */
namespace QUI\System\Console\Tools;

use QUI;
use QUI\System\Console\Tool;

/**
 * Class RenameProject
 *
 * @package QUI\System\Console\Tools
 */
class DeleteProject extends Tool
{
    /**
     * DeleteProject constructor.
     */
    public function __construct()
    {
        $this->setName("quiqqer:delete-project");
        $this->setDescription(
            QUI::getLocale()->get("quiqqer/quiqqer", "console.tool.project.delete.description")
        );
    }

    /**
     * Executes the tool
     */
    public function execute()
    {
        $this->writeLnLocale("console.tool.project.delete.warning.header", "yellow");
        $this->writeLnLocale("console.tool.project.delete.warning", "white");
        $this->writeLn("");
        $this->writeLnLocale("console.tool.project.delete.prompt.projectname.info", "cyan");
        foreach (QUI::getProjectManager()->getProjects() as $projectName) {
            $this->writeLn(" * ". $projectName);
        }
        $this->writeLnLocale("console.tool.project.delete.prompt.projectname", "light_cyan");
        $projectName = $this->readInput();

        try {
            $Project = QUI::getProject($projectName);
        } catch (\Exception $Exception) {
            $this->writeLnLocale("console.tool.project.delete.project.not.found", "light_red");
            $this->writeLn("");
            exit;
        }

        // Check if this project is the only one
        if (QUI::getProjectManager()->count() == 1) {
            $this->writeLnLocale("console.tool.project.delete.project.delete.last.project", "light_red");
            $this->writeLn("");
            exit;
        }


        // Confirm project deletion
        $this->writeLnLocale("console.tool.project.delete.prompt.warning.confirm", "yellow");
        $this->writeLnLocale("console.tool.project.delete.prompt.projectname.confirm", "light_cyan");
        $confirm = $this->readInput();

        if ($confirm != $projectName) {
            $this->writeLnLocale("console.tool.project.delete.error.confirm.mismatch", "light_red");
            $this->writeLn("");
            exit;
        }

        QUI::getProjectManager()->deleteProject($Project);

        $this->writeLnLocale("console.tool.project.delete.success.finished", "light_green");
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
}
