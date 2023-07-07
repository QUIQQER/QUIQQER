<?php

/**
 * This file contains the \QUI\System\Console\Tools\Project
 */

namespace QUI\System\Console\Tools;

use QUI;
use QUI\Bricks\Manager as BricksManager;
use QUI\Projects\Manager as ProjectsManager;

/**
 * Class Project
 */
class Project extends QUI\System\Console\Tool
{
    protected bool $quiqqerBricksInstalled = false;

    protected BricksManager $BricksManager;

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

        $this->addArgument(
            'copy',
            'Copy a project from one language to another',
            false,
            false
        );

        $this->addArgument(
            'lang_from',
            'Copy project -> From language',
            false,
            false
        );

        $this->addArgument(
            'lang_to',
            'Copy project -> To language',
            false,
            false
        );

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
        $copy = $this->getArgument('copy');

        if ($create) {
            $this->createProject();

            return;
        }

        if ($delete) {
            $this->deleteProject();

            return;
        }

        if ($copy) {
            $this->copyProject();

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
                $this->writeLn('- ' . $installedTemplate['name']);
            }

            $this->writeLn();
            $this->writeLn('For none, leave empty. :');
            $template = $this->readInput();

            try {
                $Package = QUI::getPackageManager()->getInstalledPackage($template);
                $composerData = $Package->getComposerData();

                if (
                    !isset($composerData['type']) ||
                    $composerData['type'] !== 'quiqqer-template'
                ) {
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
                'langs' => \implode(',', $projectLanguages)
            ]);

            if ($demoData) {
                QUI\Utils\Project::applyDemoDataToProject(
                    QUI::getProject($projectName),
                    $template
                );
            }
        } catch (\Exception $Exception) {
            $this->writeLn('Could not create project: ' . $Exception->getMessage());

            return;
        }

        $this->writeLn('Project ' . $projectName . ' successfuly created.');
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
            $this->writeLn(" * " . $projectName);
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
        $text = QUI::getLocale()->get("quiqqer / quiqqer", $locale);

        $this->writeLn($text, $color, $background);
    }

    /**
     * Copy project from one language to another.
     *
     * @return void
     */
    protected function copyProject()
    {
        $this->quiqqerBricksInstalled = QUI::getPackageManager()->isInstalled('quiqqer/bricks');

        if ($this->quiqqerBricksInstalled) {
            $this->BricksManager = BricksManager::init();
        }

        // project name
        $projectName = $this->getArgument('projectname');

        if (!$projectName) {
            $this->writeLn('Enter the name of the project that is to be copied: ');
            $projectName = $this->readInput();
        }

        $langFrom = $this->getArgument('lang_from');

        if (!$langFrom) {
            $this->writeLn('Enter project language that is copied FROM: ');
            $langFrom = $this->readInput();
        }

        try {
            $Project = QUI::getProject($projectName, $langFrom);
        } catch (\Exception $Exception) {
            $this->writeLn("ERROR: Could not load project -> " . $Exception->getMessage(), 'red');
            exit(1);
        }

        $langTo = $this->getArgument('lang_to');

        if (!$langTo) {
            $this->writeLn('Enter project language that is copied TO: ');
            $langTo = $this->readInput();
        }

        if ($langFrom === $langTo) {
            $this->writeLn("ERROR: Copy languages must not be identical.", 'red');
            exit(1);
        }

        $projectLangs = $Project->getLanguages();

        if (!\in_array($langTo, $projectLangs)) {
            $this->writeLn("Project lang '${langTo}' does not exist. Adding language...");

            $projectLangs[] = $langTo;

            try {
                ProjectsManager::setConfigForProject(
                    $Project->getName(),
                    [
                        'lang' => $projectLangs
                    ]
                );

                $this->write(" SUCCESS!");
            } catch (\Exception $Exception) {
                $this->writeLn("ERROR: " . $Exception->getMessage(), 'red');
                exit(1);
            }
        }

        // Target project cleanup
        $TargetProject = QUI::getProjectManager()->getProject($Project->getName(), $langTo);
        $TargetRootSite = new QUI\Projects\Site\Edit($TargetProject, 1);

        if (!empty($TargetRootSite->getChildrenIds(['active' => '0&1']))) {
            $this->writeLn(" === ATTENTION ===");
            $this->writeLn(
                "The target project is NOT empty and does contain children sites."
                . " In order for a complete language copy to work it should be empty."
            );

            $this->writeLn(
                "\nDelete all sites for project \"" . $Project->getName(
                ) . "\" (language: \"" . $langTo . "\") now? (Y/n) "
            );

            $confirm = \mb_strtolower($this->readInput());

            if ($confirm === 'n') {
                $this->writeLn("Aborting script because target project is not empty.");
                exit(0);
            }

            $this->writeLn("\nDeleting all sites in target project...");

            foreach ($TargetRootSite->getChildrenIdsRecursive(['active' => '0&1']) as $siteId) {
                $Site = new QUI\Projects\Site\Edit($TargetProject, $siteId);
                $Site->delete();
                $Site->destroy();
            }

            $this->write(" SUCCESS!\n\n");
        }

        // Target project bricks cleanup
        if ($this->quiqqerBricksInstalled) {
            $targetProjectBricks = $this->BricksManager->getBricksFromProject($TargetProject);

            if (!empty($targetProjectBricks)) {
                $this->writeLn(" === ATTENTION ===");
                $this->writeLn(
                    "The target project has already existing bricks. Should these bricks be deleted before the"
                    . " copy process?"
                );

                $this->writeLn(
                    "\nDelete all bricks for project \"" . $Project->getName(
                    ) . "\" (language: \"" . $langTo . "\") now? (Y/n) "
                );

                $confirm = \mb_strtolower($this->readInput());

                if ($confirm !== 'n') {
                    $this->writeLn("Deleting bricks...");

                    // Fetch brick IDs from database (because Brick class does not offer ->getId())
                    $result = QUI::getDataBase()->fetch([
                        'select' => ['id'],
                        'from' => $this->BricksManager::getTable(),
                        'where' => [
                            'project' => $TargetProject->getName(),
                            'lang' => $TargetProject->getLang()
                        ]
                    ]);

                    /** @var QUI\Bricks\Brick $Brick */
                    foreach ($result as $row) {
                        $this->BricksManager->deleteBrick($row['id']);
                    }

                    $this->writeLn(" SUCCESS!");
                }
            }
        }

        $this->writeLn("\n=== Starting copy process ===\n");

        $this->copySiteLevel($Project->firstChild(), $langTo);
    }

    /**
     * Takes a $RootSite and creates language copies of all its children.
     *
     * @param QUI\Projects\Site $RootSite
     * @param string $langTo
     * @param QUI\Projects\Site|null $ParentSite - Parent site of $RootSite
     */
    protected function copySiteLevel(QUI\Projects\Site $RootSite, string $langTo, ?QUI\Projects\Site $ParentSite = null)
    {
        $Project = $RootSite->getProject();
        $TargetProject = QUI::getProjectManager()->getProject($Project->getName(), $langTo);

        $RootSiteCopy = $this->copySite($TargetProject, $RootSite, $ParentSite ? $ParentSite->getId() : null);

        foreach ($RootSite->getChildrenIds(['active' => '0&1']) as $siteId) {
            $Site = new QUI\Projects\Site\Edit($Project, $siteId);

//            if (!empty($Site->getChildrenIds(['active' => '0&1']))) {
            $this->copySiteLevel($Site, $langTo, $RootSiteCopy);
//            }

//            $SiteCopy = $this->copySite($TargetProject, $Site, $ParentSite->getId());
//
//            if (!empty($Site->getChildrenIds(['active' => '0&1']))) {
//                $this->copySiteLevel($Site, $langTo, $SiteCopy);
//            }
        }
    }

    /**
     * Make a complete language copy of a site including bricks.
     *
     * @param QUI\Projects\Project $TargetProject
     * @param QUI\Projects\Site $Site - The site that is copied
     * @param int|null $copyParentId (optional) - Parent site id of the copy [default: no parent - $Site is root site (ID 1)]
     * @return QUI\Projects\Site - Language copy of $Site
     *
     * @throws QUI\Exception
     */
    protected function copySite(
        QUI\Projects\Project $TargetProject,
        QUI\Projects\Site $Site,
        ?int $copyParentId = null
    ): QUI\Projects\Site {
        $this->writeLn("START COPY Site #" . $Site->getId());

        $SystemUser = QUI::getUsers()->getSystemUser();
        $langTo = $TargetProject->getLang();

        // Temporarily remove tags
        $Site->setAttribute('quiqqer.tags.tagList', false);
        $Site->setAttribute('quiqqer.tags.tagGroups', false);

        if (empty($copyParentId)) {
            $NewSite = new QUI\Projects\Site\Edit($TargetProject, 1);
            $NewSite->setAttributes($Site->getAttributes());

            $NewSite->save($SystemUser);
        } else {
            $NewSite = $Site->copy($copyParentId, $TargetProject);
        }

        $this->writeLn(" -> Copy successful");

        // Bricks
        if ($this->quiqqerBricksInstalled) {
            $this->writeLn(" -> Copying bricks...");

            $siteAreas = $Site->getAttribute('quiqqer.bricks.areas');

            if (!empty($siteAreas)) {
                $siteAreas = \json_decode($siteAreas, true);
                $newSiteAreas = [];

                foreach ($siteAreas as $area => $bricks) {
                    $newSiteAreas[$area] = [];

                    foreach ($bricks as $brick) {
                        $Brick = $this->BricksManager->getBrickById($brick['brickId']);

                        $copyBrickId = $this->BricksManager->copyBrick(
                            $brick['brickId'],
                            [
                                'lang' => $langTo
                            ]
                        );

                        $newSiteAreas[$area][] = [
                            'brickId' => $copyBrickId,
                            'customfields' => $Brick->getCustomFields(),
                            'uid' => ''
                        ];
                    }
                }

                $NewSite->setAttribute('quiqqer.bricks.areas', \json_encode($newSiteAreas));
                $NewSite->save($SystemUser);
            }

            $this->writeLn(" -> Bricks copy successful");
        }

        // Add language link
        $Edit = new QUI\Projects\Site\Edit($Site->getProject(), $Site->getId());
        $Edit->addLanguageLink($langTo, $NewSite->getId());

        // Activate
        if ($Edit->getAttribute('active')) {
            try {
                $this->writeLn("-> Activating Site...");
                $NewSite->activate($SystemUser);
                $this->write(" SUCCESS!");
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
                $this->write(" ERROR: " . $Exception->getMessage());
            }
        }

        return $NewSite;
    }
}
