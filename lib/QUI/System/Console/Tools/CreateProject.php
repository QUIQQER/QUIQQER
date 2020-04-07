<?php

/**
 * This file contains the \QUI\System\Console\Tools\CreateProject
 */

namespace QUI\System\Console\Tools;

use QUI;

/**
 * Create a new Project in the quiqqer console
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class CreateProject extends QUI\System\Console\Tool
{
    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->setName('quiqqer:create-project')
            ->setDescription('Create a new project')
            ->addArgument('projectname', 'Name of the project', 'p', true)
            ->addArgument(
                'projectlangs',
                'Langs of the project (comma separated)',
                'l',
                true
            )
            ->addArgument(
                'template',
                'Standard template of the project',
                false,
                true
            );
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
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
        $demoData = true;
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
}
