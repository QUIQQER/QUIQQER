<?php

/**
 * This file contains the \QUI\System\Console\Tools\CreateProject
 */

namespace QUI\System\Console\Tools;

use QUI;

/**
 * Copy the site structure of a project from one language to another
 *
 * @author  www.pcsg.de (Patrick Müller)
 */
class CopyLanguageSites extends QUI\System\Console\Tool
{
    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->setName('quiqqer:copy-language-sites')
            ->setDescription('Copy the site structure of a project from one language to another')
            ->addArgument('projectname', 'Name of the project', 'p')
            ->addArgument(
                'sourcelang',
                'Source lang of the project',
                'slang'
            )
            ->addArgument(
                'targetlang',
                'Target lang of the project',
                'tlang'
            )
            ->addArgument(
                'sourceparentid',
                'Root Site ID of source language',
                'sparentid'
            )->addArgument(
                'targetparentid',
                'Root Site ID of source language',
                'tparentid'
            )->addArgument(
                'languagelink',
                'Create language link',
                'link',
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
        $Projects = QUI::getProjectManager();

        // project name
        $projectname = $this->getArgument('projectname');

        if (empty($projectname)) {
            $this->writeLn('Project name: ');
            $projectname = $this->readInput();
        }

        // source lang
        $sourcelang = $this->getArgument('sourcelang');

        if (empty($sourcelang)) {
            $this->writeLn("Source lang: ");
            $sourcelang = $this->readInput();
        }

        try {
            $SourceProject = $Projects->getProject($projectname, $sourcelang);
        } catch (\Exception $Exception) {
            $this->writeLn("Could not load project $projectname ($sourcelang)");
            $this->execute();
        }

        // source parent id
        $sourceparentid = $this->getArgument('sourceparentid');

        if (empty($sourceparentid)) {
            $this->writeLn("Source Parent ID [1]: ");
            $sourceparentid = $this->readInput();
        }

        if (empty($sourceparentid)) {
            $sourceparentid = 1;
        }

        try {
            $SourceProject->get($sourceparentid);
        } catch (\Exception $Exception) {
            $this->writeLn("Could not load source site $sourceparentid ($sourcelang)");
        }

        // target lang
        $targetlang = $this->getArgument('targetlang');

        if (empty($targetlang)) {
            $this->writeLn("Target lang: ");
            $targetlang = $this->readInput();
        }

        try {
            $TargetProject = $Projects->getProject($projectname, $targetlang);
        } catch (\Exception $Exception) {
            $this->writeLn("Could not load project $projectname ($targetlang)");
            $this->execute();
        }

        // target parent id
        $targetparentid = $this->getArgument('targetparentid');

        if (empty($targetparentid)) {
            $this->writeLn("Target Parent ID [1]: ");
            $targetparentid = $this->readInput();
        }

        if (empty($targetparentid)) {
            $targetparentid = 1;
        }

        try {
            $TargetProject->get($targetparentid);
        } catch (\Exception $Exception) {
            $this->writeLn("Could not load source site $targetparentid ($targetlang)");
        }

        $languagelink = $this->getArgument('languagelink');

        if (empty($languagelink)) {
            $this->writeLn("Create language links? [y/N]: ");
            $languagelink = $this->readInput();
        }

        if (mb_strtolower($languagelink) === 'y') {
            $languagelink = true;
        } else {
            $languagelink = false;
        }

        $this->copyRecursive(
            $SourceProject,
            $TargetProject,
            $sourceparentid,
            $targetparentid,
            $languagelink
        );
    }

    /**
     * Copy sites from a SourceProject to a TargetProject
     *
     * @param QUI\Projects\Project $SourceProject
     * @param QUI\Projects\Project $TargetProject
     * @param int $sourceid
     * @param int $targetid
     * @param bool $link (optional) - create language link [default: false]
     * @return void
     *
     * @throws QUI\Exception
     */
    protected function copyRecursive($SourceProject, $TargetProject, $sourceid, $targetid, $link = false)
    {
        $SourceParentSite  = new QUI\Projects\Site\Edit($SourceProject, $sourceid);
        $sourceChildrenIds = $SourceParentSite->getChildrenIds(array(
            'active' => '0&1'
        ));

        if (empty($sourceChildrenIds)) {
            return;
        }

        foreach ($sourceChildrenIds as $sourceChildId) {
            $SourceChild = new QUI\Projects\Site\Edit($SourceProject, $sourceChildId);

            $TargetCopyChild = $SourceChild->copy(
                $targetid,
                $TargetProject
            );

            if ($link) {
                $SourceChild->addLanguageLink($TargetProject->getLang(), $TargetCopyChild->getId());
            }

            $this->copyRecursive(
                $SourceProject,
                $TargetProject,
                $SourceChild->getId(),
                $TargetCopyChild->getId(),
                $link
            );
        }
    }
}
