<?php


/**
 * This file contains the \QUI\System\Console\Tools\Project
 */

namespace QUI\System\Console\Tools;

use QUI;

/**
 * Class Project
 */
class Project extends QUI\System\Console\Tool
{
    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->systemTool = true;

        $this->setName('quiqqer:project')
            ->setDescription('Project management')
            ->addArgument(
                'create',
                'Create a new project',
                'c',
                true
            )
            ->addArgument(
                'delete',
                'Delete a project',
                'd',
                true
            )
            ->addArgument('projectname', 'Name of the project', 'p', true);

        $this->addExample(
            './console quiqqer:project create --projectname="test" --projectlangs="de,en"'
        );

        $this->addExample(
            './console quiqqer:project create --projectname="test" --projectlangs="de,en" --template="quiqqer/template-businesspro"'
        );

        $this->addExample(
            './console quiqqer:project delete --projectname="test"'
        );
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        $create = $this->getArgument('create');
        $delete = $this->getArgument('delete');

        if ($create) {
            $this->createProject();

            return;
        }

        if ($delete) {
            $this->deleteProject();

            return;
        }

        $this->outputHelp();
    }

    /**
     * Create a new project
     */
    protected function createProject()
    {
        // project name
        $projectName = $this->getArgument('projectname');

        if (!$projectName) {
            $this->writeLn('Enter the name of the new Project: ');
            $projectName = $this->readInput();
        }

        try {
            QUI::getProject($projectName);
            $this->writeLn('This project already exists!', 'red');
            $this->resetColor();
            $this->writeLn('', '');
            exit;
        } catch (QUI\Exception $Exception) {
        }

        // project languages
        $projectLanguages = $this->getArgument('projectlangs');

        if (!$projectLanguages) {
            $this->write('Enter the available languages (comma separated): ');
            $projectLanguages = $this->readInput();
        }

        $projectLanguages = \explode(',', $projectLanguages);

        // project standard language
        $projectLanguage = $this->getArgument('projectlang');

        if (!$projectLanguage && \count($projectLanguages) === 1) {
            $projectLanguage = $projectLanguages[0];
        }

        if (!$projectLanguage) {
            $this->write('Which should be the standard language? : ');
            $projectLanguage = $this->readInput();
        }

        // project standard template
        $template = $this->getArgument('template');

        if (!$template) {
            $this->writeLn('Which should be the standard template?');

            $installedTemplates = QUI::getPackageManager()->searchInstalledPackages([
                'type' => 'quiqqer-template'
            ]);

            foreach ($installedTemplates as $installedTemplate) {
                $this->writeLn('- '.$installedTemplate['name']);
            }

            $this->writeLn();
            $this->writeLn('For none, leave empty. :');
            $template = $this->readInput();

            try {
                $Package      = QUI::getPackageManager()->getInstalledPackage($template);
                $composerData = $Package->getComposerData();

                if (!isset($composerData['type']) ||
                    $composerData['type'] !== 'quiqqer-template') {
                    $this->writeLn('This template doesn \'t exists!', 'red');
                    $this->resetColor();
                    $this->writeLn('', '');
                    exit;
                }
            } catch (QUI\Exception $Exception) {
                $this->writeLn($Exception->getMessage(), 'red');
                $this->resetColor();
                $this->writeLn('', '');
                exit;
            }
        }

        // demodata
        $this->write('Should demo data be used? [Y/n] :');
        $demoData    = true;
        $useDemoData = $this->readInput();

        if ($useDemoData === 'n') {
            $demoData = false;
        }

        try {
            QUI::getProjectManager()->createProject(
                $projectName,
                $projectLanguage
            );

            QUI::getProjectManager()->setConfigForProject($projectName, [
                'template' => $template,
                'langs'    => \implode(',', $projectLanguages)
            ]);

            if ($demoData) {
                QUI\Utils\Project::applyDemoDataToProject(
                    QUI::getProject($projectName),
                    $template
                );
            }
        } catch (\Exception $Exception) {
            $this->writeLn('Could not create project: '.$Exception->getMessage());

            return;
        }

        $this->writeLn('Project '.$projectName.' successfuly created.');
        $this->writeLn('');
    }

    /**
     * Delete a project
     */
    protected function deleteProject()
    {
        $this->writeLnLocale("console.tool.project.delete.warning.header", "yellow");
        $this->writeLnLocale("console.tool.project.delete.warning", "white");
        $this->writeLn("");
        $this->writeLnLocale("console.tool.project.delete.prompt.projectname.info", "cyan");

        foreach (QUI::getProjectManager()->getProjects() as $projectName) {
            $this->writeLn(" * ".$projectName);
        }

        $this->writeLn();
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
