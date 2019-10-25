<?php

/**
 * This file contains the \QUI\System\Console\Tools\CreateProject
 */

namespace QUI\System\Console\Tools;

use QUI;

/**
 * Set attributes for a selection of Sites
 *
 * @author  www.pcsg.de (Patrick Müller)
 */
class SetSiteAttributes extends QUI\System\Console\Tool
{
    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->setName('quiqqer:set-site-attributes')
            ->setDescription('Set attributes for a selection of Sites')
            ->addArgument(
                'projectname',
                'Name of the project',
                'pname'
            )
            ->addArgument(
                'projectlang',
                'Language of the project',
                'plang'
            );
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        $Projects = QUI::getProjectManager();

        // project name
        $projectname = $this->getArgument('projectname');
        $projectlang = $this->getArgument('projectlang');

        try {
            $Project = $Projects->getProject($projectname, $projectlang);
        } catch (\Exception $Exception) {
            $this->writeLn("Could not load project $projectname ($projectlang)");
            $this->execute();
        }

        $this->writeLn("Site query (MySQL): WHERE ");
        $siteQuery = \trim($this->readInput());

        $sql = "SELECT `id` FROM ".QUI::getDBProjectTableName('sites', $Project);
        $sql .= " WHERE $siteQuery";

        $this->writeLn("\nQuerying sites: ".$sql);

        try {
            $siteIds = QUI::getDataBase()->fetchSQL($sql);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            $this->writeLn("\n\nERROR: Query failed -> ".$Exception->getMessage());
            exit(1);
        }

        $this->writeLn("Found ".count($siteIds)." sites.");

        // Set site attributes
        $this->writeLn("\n====== Set site attributes ======");

        $placeholderDesc = [
            'id'    => 'Site ID',
            'title' => 'Site title',
            'true'  => 'Boolean true',
            'false' => 'Boolean false'
        ];

        $this->writeLn("\nThe following placeholders can be used\n");

        foreach ($placeholderDesc as $placeholder => $desc) {
            $this->writeLn("[$placeholder] -> $desc");
        }

        $attributes = [];

        do {
            $this->writeLn("\nSet attribute (key): ");
            $attrKey = \trim($this->readInput());

            $this->writeLn("Value: ");
            $attrVal = \trim($this->readInput());

            $attributes[$attrKey] = $attrVal;

            $this->writeLn("Set another attribute? (Y/n): ");
            $setNew = $this->readInput();

            if (\mb_strtolower($setNew) === 'n') {
                break;
            }
        } while (true);


        $this->writeLn("\nThe following site attributes will be set:\n");

        foreach ($attributes as $k => $v) {
            $this->writeLn("$k => $v");
        }

        $this->writeLn("\nIs this OK? (Y/n): ");
        $confirm = $this->readInput();

        if (\mb_strtolower($confirm) === 'n') {
            $this->writeLn("Exit.");
            exit(0);
        }

        $SystemUser = QUI::getUsers()->getSystemUser();

        foreach ($siteIds as $row) {
            $siteId = $row['id'];
            $this->writeLn("\nEdit Site #$siteId...");

            try {
                $Site = new QUI\Projects\Site\Edit($Project, $siteId);
                $this->write(" OK!");
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
                $this->write(" ERROR: ".$Exception->getMessage());
                continue;
            }

            foreach ($attributes as $k => $v) {
                $output = $v;

                foreach ($placeholderDesc as $placeholder => $desc) {
                    $placeholder = '['.$placeholder.']';
                    if (\mb_strpos($v, $placeholder) === false) {
                        continue;
                    }

                    switch ($placeholder) {
                        case '[id]':
                            $v      = \str_replace($placeholder, $Site->getId(), $v);
                            $output = $v;
                            break;

                        case '[title]':
                            $v      = \str_replace($placeholder, $Site->getAttribute('title'), $v);
                            $output = $v;
                            break;

                        case '[true]':
                            $v      = true;
                            $output = 'true (bool)';
                            break;

                        case '[false]':
                            $v      = false;
                            $output = 'false (bool)';
                            break;
                    }
                }

                $this->writeLn("Set \"$k\" to $output");

                try {
                    $this->writeLn("\nSaving Site...");
                    $Site->setAttribute($k, $v);
                    $Site->unlockWithRights();
                    $Site->save($SystemUser);

                    $this->write(" OK!");
                } catch (\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);
                    $this->write(" ERROR: ".$Exception->getMessage());
                }
            }
        }

        $this->writeLn("\n\nFinished.\n\n");
        exit(1);
    }
}
