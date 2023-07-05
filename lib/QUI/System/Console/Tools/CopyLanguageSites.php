<?php

/**
 * This file contains the \QUI\System\Console\Tools\CreateProject
 */

namespace QUI\System\Console\Tools;

use QUI;
use QUI\Bricks\Manager as BricksManager;

/**
 * Copy the site structure of a project from one language to another
 *
 * @author  www.pcsg.de (Patrick Müller)
 */
class CopyLanguageSites extends QUI\System\Console\Tool
{
    /**
     * @var ?BricksManager
     */
    protected ?BricksManager $BricksManager = null;

    /**
     * Maps source brick id to target brick id.
     *
     * @var array
     */
    protected array $bricksMapping = [];

    /**
     * @var bool
     */
    protected bool $copyBricks = false;

    /**
     * @var array
     */
    protected array $sourceBrickAreas = [];

    /**
     * @var bool
     */
    protected bool $activateSites = true;

    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->setName('quiqqer:copy-language-sites')
            ->setDescription('Copy the site structure of a project from one language to another')
            ->addArgument(
                'project_name',
                'Name of the project',
                'p'
            )
            ->addArgument(
                'source_lang',
                'Source lang of the project',
                'slang'
            )
            ->addArgument(
                'target_lang',
                'Target lang of the project',
                'tlang'
            )
            ->addArgument(
                'source_parent_id',
                'Root Site ID of source language',
                'sparentid'
            )
            ->addArgument(
                'target_parent_id',
                'Root Site ID of source language',
                'tparentid'
            )
            ->addArgument(
                'create_language_links',
                'Create language links',
                'link',
                true
            )
            ->addArgument(
                'copy_bricks',
                'Copy bricks to the target language and assign them to the corresponding site(s).',
                false,
                true
            )
            ->addArgument(
                'do_not_activate',
                'Do NOT activate a copied Site if the source Site is active. This leaves all Site copies inactive!',
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
        $this->BricksManager = BricksManager::init();

        $Projects = QUI::getProjectManager();

        if (!empty($this->getArgument('do_not_activate'))) {
            $this->activateSites = false;
        }

        // project name
        $projectname = $this->getArgument('project_name');

        if (empty($projectname)) {
            $this->writeLn('Project name: ');
            $projectname = $this->readInput();
        }

        // source lang
        $source_lang = $this->getArgument('source_lang');

        if (empty($source_lang)) {
            $this->writeLn("Source lang: ");
            $source_lang = $this->readInput();
        }

        try {
            $SourceProject = $Projects->getProject($projectname, $source_lang);
        } catch (\Exception $Exception) {
            $this->writeLn("Could not load project $projectname ($source_lang)");
            $this->execute();
        }

        // source parent id
        $sourceParentId = $this->getArgument('source_parent_id');

        if (empty($sourceParentId)) {
            $this->writeLn("Source Parent ID [1]: ");
            $sourceParentId = $this->readInput();
        }

        if (empty($sourceParentId)) {
            $sourceParentId = 1;
        }

        try {
            $SourceProject->get($sourceParentId);
        } catch (\Exception $Exception) {
            $this->writeLn("Could not load source site $sourceParentId ($source_lang)");
        }

        // target lang
        $targetLang = $this->getArgument('target_lang');

        if (empty($targetLang)) {
            $this->writeLn("Target lang: ");
            $targetLang = $this->readInput();
        }

        try {
            $TargetProject = $Projects->getProject($projectname, $targetLang);
        } catch (\Exception $Exception) {
            $this->writeLn("Could not load project $projectname ($targetLang)");
            $this->execute();
        }

        // target parent id
        $targetParentId = $this->getArgument('target_parent_id');

        if (empty($targetParentId)) {
            $this->writeLn("Target Parent ID [1]: ");
            $targetParentId = $this->readInput();
        }

        if (empty($targetParentId)) {
            $targetParentId = 1;
        }

        try {
            $TargetProject->get($targetParentId);
        } catch (\Exception $Exception) {
            $this->writeLn("Could not load source site $targetParentId ($targetLang)");
        }

        $createLanguageLinks = $this->getArgument('create_language_links');

        if (empty($createLanguageLinks)) {
            $this->writeLn("Create language links? [y/N]: ");
            $createLanguageLinks = $this->readInput();

            if (mb_strtolower($createLanguageLinks) === 'y') {
                $createLanguageLinks = true;
            } else {
                $createLanguageLinks = false;
            }
        } else {
            $createLanguageLinks = true;
        }

        $copyBricks = $this->getArgument('copy_bricks');

        if (empty($copyBricks)) {
            $this->writeLn("Copy bricks? [y/N]: ");
            $copyBricks = $this->readInput();
        }

        if ($copyBricks || mb_strtolower($copyBricks) === 'y') {
            $this->copyBricks = true;
            $this->sourceBrickAreas = $this->BricksManager->getAreasByProject($SourceProject);

            $this->copyBricks($SourceProject, $TargetProject);
        }

        $this->writeLn("\n\n=== Copying sites ===\n\n");

        $this->copyRecursive(
            $SourceProject,
            $TargetProject,
            $sourceParentId,
            $targetParentId,
            $createLanguageLinks
        );

        $this->writeLn("\n\nScript successfully executed.\n\n");
    }

    /**
     * Copy all bricks.
     *
     * @param QUI\Projects\Project $SourceProject
     * @param QUI\Projects\Project $TargetProject
     * @return void
     */
    protected function copyBricks(QUI\Projects\Project $SourceProject, QUI\Projects\Project $TargetProject)
    {
        $this->writeLn("\n\n=== Copying bricks to target language ===\n\n");

        $sourceBricks = QUI::getDataBase()->fetch([
            'select' => ['id'],
            'from' => $this->BricksManager->getTable(),
            'where' => [
                'project' => $SourceProject->getName(),
                'lang' => $SourceProject->getLang()
            ]
        ]);

        foreach ($sourceBricks as $brick) {
            $sourceBrickId = $brick['id'];

            $this->writeLn("Copy brick #" . $sourceBrickId . "...");

            try {
                $targetBrickId = $this->BricksManager->copyBrick(
                    $sourceBrickId,
                    [
                        'project' => $TargetProject->getName(),
                        'lang' => $TargetProject->getLang()
                    ]
                );

                $this->bricksMapping[$sourceBrickId] = $targetBrickId;

                $this->write(" SUCCESS!");
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
                $this->write(" ERROR: " . $Exception->getMessage());
            }
        }
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
        $SourceParentSite = new QUI\Projects\Site\Edit($SourceProject, $sourceid);
        $sourceChildrenIds = $SourceParentSite->getChildrenIds([
            'active' => '0&1'
        ]);

        if (empty($sourceChildrenIds)) {
            return;
        }

        foreach ($sourceChildrenIds as $sourceChildId) {
            $this->writeLn("Copy Site #" . $sourceChildId . "...");

            $SourceChild = new QUI\Projects\Site\Edit($SourceProject, $sourceChildId);

            try {
                $TargetCopyChild = $SourceChild->copy(
                    $targetid,
                    $TargetProject
                );

                $this->write(" SUCCESS!");
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
                $this->write(" ERROR: " . $Exception->getMessage());

                continue;
            }

            if ($link) {
                $this->writeLn(" -> Adding language link...");

                try {
                    $SourceChild->addLanguageLink($TargetProject->getLang(), $TargetCopyChild->getId());
                    $this->write(" SUCCESS!");
                } catch (\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);
                    $this->write(" ERROR: " . $Exception->getMessage());
                }
            }

            if ($this->copyBricks) {
                $this->writeLn(" -> Adding bricks...");

                $siteAreaBricks = [];

                foreach ($this->sourceBrickAreas as $brickArea) {
                    $brickArea = $brickArea['name'];
                    $bricksByArea = $this->BricksManager->getBricksByArea($brickArea, $SourceChild);

                    if (empty($bricksByArea)) {
                        continue;
                    }

                    $siteAreaBricks[$brickArea] = [];

                    /** @var QUI\Bricks\Brick $Brick */
                    foreach ($bricksByArea as $Brick) {
                        $brickId = (int)$Brick->getAttribute('id');

                        if (isset($this->bricksMapping[$brickId])) {
                            $siteAreaBricks[$brickArea][] = [
                                'brickId' => $this->bricksMapping[$brickId],
                                'customfields' => '',
                                'uid' => ''
                            ];
                        }
                    }
                }

                if (!empty($siteAreaBricks)) {
                    $this->write(" SUCCESS!");

                    $TargetCopyChild->setAttribute('quiqqer.bricks.areas', \json_encode($siteAreaBricks));
                    $TargetCopyChild->save(QUI::getUsers()->getSystemUser());
                } else {
                    $this->write(" no bricks found to add in source Site.");
                }
            }

            if ($this->activateSites && $SourceChild->getAttribute('active')) {
                $this->writeLn(" -> Activating Site...");

                try {
                    $TargetCopyChild->activate(QUI::getUsers()->getSystemUser());
                    $this->write(" SUCCESS!");
                } catch (\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);
                    $this->write(" ERROR: " . $Exception->getMessage());
                }
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
