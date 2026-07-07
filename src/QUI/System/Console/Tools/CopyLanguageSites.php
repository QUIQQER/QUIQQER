<?php

/**
 * This file contains the \QUI\System\Console\Tools\CreateProject
 */

namespace QUI\System\Console\Tools;

use Exception;
use QUI;

use function json_encode;

/**
 * Copy the site structure of a project from one language to another
 */
class CopyLanguageSites extends QUI\System\Console\Tool
{
    protected ?QUI\Bricks\Manager $BricksManager = null;

    /**
     * Maps source brick id to target brick id.
     *
     * @var array<int|string, int|string>
     */
    protected array $bricksMapping = [];

    protected bool $copyBricks = false;

    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $sourceBrickAreas = [];

    protected bool $activateSites = true;

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
     * @throws QUI\Exception
     */
    public function execute(): void
    {
        if (class_exists('QUI\Bricks\Manager')) {
            $this->BricksManager = QUI\Bricks\Manager::init();
        }

        $Projects = QUI::getProjectManager();

        if (!empty($this->getArgument('do_not_activate'))) {
            $this->activateSites = false;
        }

        // project name
        $projectName = $this->getArgument('project_name');

        if (empty($projectName)) {
            $this->writeLn('Project name: ');
            $projectName = $this->readInput();
        }

        // source lang
        $source_lang = $this->getArgument('source_lang');

        if (empty($source_lang)) {
            $this->writeLn("Source lang: ");
            $source_lang = $this->readInput();
        }

        try {
            $SourceProject = $Projects->getProject($projectName, $source_lang);
        } catch (Exception) {
            $this->writeLn("Could not load project $projectName ($source_lang)");
            $this->execute();

            return;
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
        } catch (Exception) {
            $this->writeLn("Could not load source site $sourceParentId ($source_lang)");
        }

        // target lang
        $targetLang = $this->getArgument('target_lang');

        if (empty($targetLang)) {
            $this->writeLn("Target lang: ");
            $targetLang = $this->readInput();
        }

        try {
            $TargetProject = $Projects->getProject($projectName, $targetLang);
        } catch (Exception) {
            $this->writeLn("Could not load project $projectName ($targetLang)");
            $this->execute();

            return;
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
        } catch (Exception) {
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

        if (
            $copyBricks
            && mb_strtolower($copyBricks) !== 'n'
            && $this->BricksManager
        ) {
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
     * @throws QUI\Database\Exception
     */
    protected function copyBricks(QUI\Projects\Project $SourceProject, QUI\Projects\Project $TargetProject): void
    {
        if (!$this->BricksManager) {
            return;
        }

        $this->writeLn("\n\n=== Copying bricks to target language ===\n\n");

        $sourceBricks = QUI::getDataBaseConnection()->createQueryBuilder()
            ->select('id')
            ->from($this->BricksManager->getTable())
            ->where('project = :project')
            ->andWhere('lang = :lang')
            ->setParameter('project', $SourceProject->getName())
            ->setParameter('lang', $SourceProject->getLang())
            ->executeQuery()
            ->fetchAllAssociative();

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
            } catch (Exception $Exception) {
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
     * @param int $sourceId
     * @param int $targetId
     * @param bool $link (optional) - create language link [default: false]
     * @return void
     *
     * @throws QUI\Exception
     */
    protected function copyRecursive(
        QUI\Projects\Project $SourceProject,
        QUI\Projects\Project $TargetProject,
        int $sourceId,
        int $targetId,
        bool $link = false
    ): void {
        $SourceParentSite = new QUI\Projects\Site\Edit($SourceProject, $sourceId);
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
                    $targetId,
                    $TargetProject
                );

                $this->write(" SUCCESS!");
            } catch (Exception $Exception) {
                QUI\System\Log::writeException($Exception);
                $this->write(" ERROR: " . $Exception->getMessage());

                continue;
            }

            if ($link) {
                $this->writeLn(" -> Adding language link...");

                try {
                    $SourceChild->addLanguageLink($TargetProject->getLang(), $TargetCopyChild->getId());
                    $this->write(" SUCCESS!");
                } catch (Exception $Exception) {
                    QUI\System\Log::writeException($Exception);
                    $this->write(" ERROR: " . $Exception->getMessage());
                }
            }

            if ($this->copyBricks) {
                $this->writeLn(" -> Adding bricks...");

                $siteAreaBricks = [];

                foreach ($this->sourceBrickAreas as $brickArea) {
                    $brickArea = $brickArea['name'];
                    $bricksByArea = $this->BricksManager?->getBricksByArea($brickArea, $SourceChild);

                    if (empty($bricksByArea)) {
                        continue;
                    }

                    $siteAreaBricks[$brickArea] = [];

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

                    $TargetCopyChild->setAttribute('quiqqer.bricks.areas', json_encode($siteAreaBricks));
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
                } catch (Exception $Exception) {
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
