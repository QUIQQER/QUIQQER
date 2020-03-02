<?php

/**
 * This file contains the \QUI\System\Console\Tools\CreateProject
 */

namespace QUI\System\Console\Tools;

use QUI;

/**
 * Get attributes from a selection of Sites
 *
 * @author  www.pcsg.de (Patrick Müller)
 */
class GetSiteAttributes extends QUI\System\Console\Tool
{
    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->setName('quiqqer:get-site-attributes')
            ->setDescription('Get attributes for a selection of Sites')
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

        // Select site attributes
        $this->writeLn("\n====== Select attributes ======");

        $attributesDesc = [
            'id'  => 'Site ID',
            'url' => 'Full Site URL (with protocol)'
        ];

        $this->writeLn("\nThe following special attributess can be used\n");

        foreach ($attributesDesc as $attr => $desc) {
            $this->writeLn("[$attr] -> $desc");
        }

        $attributes = [];

        do {
            $this->writeLn("\nGet attribute (key): ");
            $attrKey = \trim($this->readInput());

            $attributes[] = $attrKey;

            $this->writeLn("Select another attribute? (Y/n): ");
            $setNew = $this->readInput();

            if (\mb_strtolower($setNew) === 'n') {
                break;
            }
        } while (true);

        $attributes = \array_values(\array_unique($attributes));

        $this->writeLn("\nThe following site attributes will be fetched:\n");

        foreach ($attributes as $attrKey) {
            $this->writeLn($attrKey);
        }

        $this->writeLn("\nIs this OK? (Y/n): ");
        $confirm = $this->readInput();

        if (\mb_strtolower($confirm) === 'n') {
            $this->writeLn("Exit.");
            exit(0);
        }

        $SystemUser        = QUI::getUsers()->getSystemUser();
        $fetchedAttributes = [];

        foreach ($siteIds as $row) {
            $siteId = $row['id'];
            $this->writeLn("\nFetching attributes from Site #$siteId...");

            try {
                $Site = new QUI\Projects\Site\Edit($Project, $siteId);
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
                $this->write(" ERROR: ".$Exception->getMessage());
                continue;
            }

            $fetchedSiteAttributes = [];

            foreach ($attributes as $attrKey) {
                switch ($attrKey) {
                    case 'url':
                        $fetchedSiteAttributes[] = $Site->getUrlRewrittenWithHost();
                        break;

                    default:
                        $fetchedSiteAttributes[] = $Site->getAttribute($attrKey);
                }
            }

            $fetchedAttributes[] = $fetchedSiteAttributes;
        }

        // Save to csv
        $varDir = QUI::getPackage('quiqqer/quiqqer')->getVarDir().'fetchedSiteAttributes/';
        QUI\Utils\System\File::mkdir($varDir);

        $csvFile = \hash('sha256', \random_bytes(128)).'.csv';
        $csvFile = $varDir.$csvFile;
        QUI\Utils\System\File::mkfile($csvFile);

        $fp = \fopen($csvFile, 'w');

        foreach ($fetchedAttributes as $row) {
            \fputcsv($fp, $row);
        }

        \fclose($fp);

        $this->writeLn("\n\nCSV File: $csvFile\n");

        $this->writeLn("\n\nFinished.\n\n");
        exit(1);
    }
}
