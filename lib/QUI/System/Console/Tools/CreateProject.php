<?php

/**
 * This file contains the \QUI\System\Console\Tools\CreateProject
 */
namespace QUI\System\Console\Tools;

/**
 * Create a new Project in the quiqqer console
 *
 * @author www.namerobot.com (Henning Leutz)
 */
class CreateProject extends \QUI\System\Console\Tool
{
    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->setName('quiqqer:create-project')
             ->setDescription('Create a new project');
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        $this->writeLn( 'Enter the name of the new Project: ' );
        $projectname = $this->readInput();

        $this->writeLn( 'Enter the available languages (comma separated): ' );
        $projectlangs = $this->readInput();
        $projectlangs = explode( ',', $projectlangs );

        $this->writeLn( 'Which is the standard language? : ' );
        $projectlang = $this->readInput();

        \QUI::getProjectManager()->createProject(
            $projectname,
            $projectlang
        );

        \QUI::getProjectManager()->setConfigForProject($projectname, array(
            'langs' => implode( ',', $projectlangs )
        ));


        $this->writeLn( 'Project successfuly created.' );
        $this->writeLn( '' );
    }
}