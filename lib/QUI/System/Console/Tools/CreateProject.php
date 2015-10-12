<?php

/**
 * This file contains the \QUI\System\Console\Tools\CreateProject
 */
namespace QUI\System\Console\Tools;

use QUI;

/**
 * Create a new Project in the quiqqer console
 *
 * @author  www.namerobot.com (Henning Leutz)
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
             ->addArgument('projectname', 'Name of the project', 'p')
             ->addArgument('projectlangs',
                 'Langs of the project (comma separated)', 'l')
             ->addArgument('template', 'Standard template of the project',
                 false, true);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        // project name
        $projectname = $this->getArgument('--projectname');

        if (!$projectname) {
            $this->writeLn('Enter the name of the new Project: ');
            $projectname = $this->readInput();
        }

        // project languages
        $projectlangs = $this->getArgument('--projectlangs');

        if (!$projectlangs) {
            $this->writeLn('Enter the available languages (comma separated): ');
            $projectlangs = $this->readInput();
        }

        $projectlangs = explode(',', $projectlangs);

        // project standard language
        $projectlang = $this->getArgument('--projectlang');

        if (!$projectlang) {
            $this->writeLn('Which should be the standard language? : ');
            $projectlang = $this->readInput();
        }

        // project standard template
        $template = $this->getArgument('--template');

        if (!$template) {
            $this->writeLn('Which should be the standard template? For none, leave empty. : ');
            $template = $this->readInput();
        }

        try {
            QUI::getProjectManager()->createProject(
                $projectname,
                $projectlang
            );

        } catch (QUI\Exception $Exception) {
            $this->writeLn('Could not create project: '
                .$Exception->getMessage());

            return;
        }

        QUI::getProjectManager()->setConfigForProject($projectname, array(
            'template' => $template,
            'langs'    => implode(',', $projectlangs)
        ));

        $this->writeLn('Project '.$projectname.' successfuly created.');
        $this->writeLn('');
    }
}