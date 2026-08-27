<?php

declare(strict_types=1);

namespace QUI\MCP;

use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\MCP\Project\CreateDefaultStructure;
use QUI\MCP\Project\CreateProject;
use QUI\MCP\Project\DeleteProject;
use QUI\MCP\Project\GetProject;
use QUI\MCP\Project\ListAvailableLanguages;
use QUI\MCP\Project\ListDemoDataSets;
use QUI\MCP\Project\ListProjectTemplates;
use QUI\MCP\Project\RenameProject;
use QUI\MCP\Project\Sites\AddLanguageLink;
use QUI\MCP\Project\Sites\ClearSiteCache;
use QUI\MCP\Project\Sites\CreateSiteCache;
use QUI\MCP\Project\Sites\GetSiteLock;
use QUI\MCP\Project\Sites\LinkSite;
use QUI\MCP\Project\Sites\ListSiteLayouts;
use QUI\MCP\Project\Sites\ListSiteTypes;
use QUI\MCP\Project\Sites\LockSite;
use QUI\MCP\Project\Sites\RemoveLanguageLink;
use QUI\MCP\Project\Sites\UnlinkSite;
use QUI\MCP\Project\Sites\UnlockSite;
use QUI\Permissions\Manager as PermissionManager;
use QUI\Permissions\Permission;
use QUI\Projects\Manager;
use QUI\Projects\ProjectIntegrationTestCase;
use QUI\Projects\Site\Edit;
use QUI\System\TestCleanup;
use ReflectionProperty;
use Throwable;

class ProjectSiteAdministrationToolIntegrationTest extends ProjectIntegrationTestCase
{
    public function testProjectAndSiteAdministrationLifecycle(): void
    {
        self::skipIfDatabaseOrSuperUserIsUnavailable();
        self::getTestProject();
        $projectName = 'phpunit_mcp_lifecycle_' . substr(md5(uniqid('', true)), 0, 8);
        $renamedProjectName = $projectName . '_renamed';

        self::runAsRootUser(function () use ($projectName, $renamedProjectName): void {
            try {
                $languageResult = self::invokeTool(new ListAvailableLanguages(), []);
                $languages = array_values(array_filter(
                    $languageResult['languages'],
                    static fn(mixed $language): bool => is_string($language) && strlen($language) === 2
                ));

                if ($languages === []) {
                    self::markTestSkipped('No installed project language is available.');
                }

                $defaultLanguage = $languages[0];
                $projectLanguages = array_slice($languages, 0, 2);
                $created = self::invokeTool(new CreateProject(), [
                    $projectName,
                    $defaultLanguage,
                    $projectLanguages
                ]);
                self::assertTrue($created['created']);
                self::assertSame($projectName, $created['project']['name']);
                self::assertSame($defaultLanguage, $created['project']['defaultLang']);

                $getResult = self::invokeTool(new GetProject(), [$projectName, $defaultLanguage]);
                self::assertSame($projectName, $getResult['project']['name']);
                self::assertArrayHasKey('template', $getResult['project']);
                self::assertArrayHasKey('host', $getResult['project']);

                $templates = self::invokeTool(new ListProjectTemplates(), []);
                self::assertIsArray($templates['templates']);

                if ($templates['templates'] !== []) {
                    $templateName = $templates['templates'][0]['name'] ?? null;

                    if (is_string($templateName) && $templateName !== '') {
                        $demoDataSets = self::invokeTool(new ListDemoDataSets(), [$templateName]);
                        self::assertSame($templateName, $demoDataSets['template']);
                        self::assertIsArray($demoDataSets['sets']);
                    }
                }

                $types = self::invokeTool(new ListSiteTypes(), []);
                self::assertContains('standard', array_column($types['types'], 'type'));
                $layouts = self::invokeTool(new ListSiteLayouts(), [$projectName, $defaultLanguage]);
                self::assertIsArray($layouts['layouts']);

                $structure = self::invokeTool(new CreateDefaultStructure(), [
                    $projectName,
                    $defaultLanguage
                ]);
                self::assertTrue($structure['created']);

                $Project = QUI::getProject($projectName, $defaultLanguage);
                $Root = $Project->firstChild()->getEdit();
                $siteId = $Root->createChild([
                    'name' => 'mcp-admin-site-' . uniqid(),
                    'title' => 'MCP administration site'
                ]);
                $otherParentId = $Root->createChild([
                    'name' => 'mcp-admin-parent-' . uniqid(),
                    'title' => 'MCP administration parent'
                ]);

                $linked = self::invokeTool(new LinkSite(), [
                    $projectName,
                    $siteId,
                    $otherParentId,
                    $defaultLanguage
                ]);
                self::assertTrue($linked['linked']);
                self::assertContains($otherParentId, (new Edit($Project, $siteId))->getParentIds());

                $unlinked = self::invokeTool(new UnlinkSite(), [
                    $projectName,
                    $siteId,
                    $otherParentId,
                    $defaultLanguage
                ]);
                self::assertTrue($unlinked['unlinked']);
                self::assertNotContains($otherParentId, (new Edit($Project, $siteId))->getParentIds());

                $initialLock = self::invokeTool(new GetSiteLock(), [
                    $projectName,
                    $siteId,
                    $defaultLanguage
                ]);
                self::assertFalse($initialLock['locked']);
                $locked = self::invokeTool(new LockSite(), [
                    $projectName,
                    $siteId,
                    $defaultLanguage
                ]);
                self::assertTrue($locked['acquired']);
                self::assertTrue($locked['lock']['ownedByRequestUser']);
                $unlocked = self::invokeTool(new UnlockSite(), [
                    $projectName,
                    $siteId,
                    $defaultLanguage
                ]);
                self::assertTrue($unlocked['released']);
                self::assertFalse($unlocked['lock']['locked']);
                $unlockedAgain = self::invokeTool(new UnlockSite(), [
                    $projectName,
                    $siteId,
                    $defaultLanguage
                ]);
                self::assertTrue($unlockedAgain['released']);
                self::assertFalse($unlockedAgain['lock']['locked']);

                $cacheCreated = self::invokeTool(new CreateSiteCache(), [
                    $projectName,
                    $siteId,
                    $defaultLanguage
                ]);
                self::assertTrue($cacheCreated['created']);
                $cacheCleared = self::invokeTool(new ClearSiteCache(), [
                    $projectName,
                    $siteId,
                    $defaultLanguage
                ]);
                self::assertTrue($cacheCleared['cleared']);

                if (count($projectLanguages) > 1) {
                    $targetLanguage = $projectLanguages[1];
                    $TargetProject = QUI::getProject($projectName, $targetLanguage);
                    $targetSiteId = $TargetProject->firstChild()->getEdit()->createChild([
                        'name' => 'mcp-language-target-' . uniqid(),
                        'title' => 'MCP language target'
                    ]);
                    self::invokeTool(new AddLanguageLink(), [
                        $projectName,
                        $siteId,
                        $targetLanguage,
                        $targetSiteId,
                        $defaultLanguage
                    ]);
                    $removedLanguageLink = self::invokeTool(new RemoveLanguageLink(), [
                        $projectName,
                        $siteId,
                        $targetLanguage,
                        $defaultLanguage
                    ]);
                    self::assertTrue($removedLanguageLink['removed']);
                    self::assertFalse($removedLanguageLink['site']['languageLinks'][$targetLanguage]['exists']);
                }

                $RequestUser = Server::getRequestUser();
                $aclValue = 'u' . $RequestUser->getUUID();
                $PermissionManager = QUI::getPermissionManager();
                $PermissionManager->setPermissions(
                    $Project,
                    ['quiqqer.project.edit' => $aclValue],
                    $RequestUser
                );
                $PermissionManager->setPermissions(
                    new Edit($Project, $siteId),
                    ['quiqqer.projects.site.view' => $aclValue],
                    $RequestUser
                );
                $PermissionManager->setPermissions(
                    $Project->getMedia()->firstChild(),
                    ['quiqqer.projects.media.view' => $aclValue],
                    $RequestUser
                );
                self::assertPermissionReferenceCounts($projectName, [1, 1, 1]);

                $renamed = self::invokeTool(new RenameProject(), [
                    $projectName,
                    $renamedProjectName
                ]);
                self::assertTrue($renamed['renamed']);
                self::assertSame($renamedProjectName, $renamed['project']['name']);
                self::assertPermissionReferenceCounts($projectName, [0, 0, 0]);
                self::assertPermissionReferenceCounts($renamedProjectName, [1, 1, 1]);

                $deleted = self::invokeTool(new DeleteProject(), [$renamedProjectName, true]);
                self::assertTrue($deleted['deleted']);
                self::assertPermissionReferenceCounts($renamedProjectName, [0, 0, 0]);
            } finally {
                self::cleanupProject($renamedProjectName);
                self::cleanupProject($projectName);
            }
        });
    }

    /**
     * @param array<int, mixed> $arguments
     * @return array<string, mixed>
     */
    private static function invokeTool(ToolInterface $Tool, array $arguments): array
    {
        $Builder = new Builder();
        $Tool->register($Builder);
        $tools = (new ReflectionProperty(Builder::class, 'tools'))->getValue($Builder);
        $Handler = $tools[0]['handler'] ?? $tools[0]['callback'] ?? null;

        self::assertIsCallable($Handler);
        $result = $Handler(...$arguments);

        self::assertIsArray($result);

        return $result;
    }

    private static function cleanupProject(string $projectName): void
    {
        try {
            TestCleanup::cleanupProject($projectName);
        } catch (Throwable) {
        } finally {
            Manager::cleanup();
            Manager::$Config = null;
            Manager::$Standard = null;
            unset(QUI::$Configs['etc/projects.ini'], QUI::$Configs['etc/projects.ini.php']);
        }
    }

    /**
     * @param array{int, int, int} $expectedCounts
     */
    private static function assertPermissionReferenceCounts(string $project, array $expectedCounts): void
    {
        $Connection = QUI::getDataBaseConnection();
        $Platform = $Connection->getDatabasePlatform();
        $table = PermissionManager::table();

        foreach (['2projects', '2sites', '2media'] as $index => $suffix) {
            $count = $Connection->executeQuery(
                'SELECT COUNT(*) FROM ' . $Platform->quoteSingleIdentifier($table . $suffix)
                . ' WHERE project = ?',
                [$project]
            )->fetchOne();

            self::assertSame($expectedCounts[$index], (int)$count);
        }
    }

    private static function skipIfDatabaseOrSuperUserIsUnavailable(): void
    {
        try {
            QUI::getDataBaseConnection()->executeQuery('SELECT 1')->free();
            $RootUser = QUI::getUsers()->get(QUI::conf('globals', 'rootuser'));
        } catch (Throwable $Exception) {
            self::markTestSkipped('QUIQQER user database is unavailable: ' . $Exception->getMessage());
        }

        if (!$RootUser->isSU()) {
            self::markTestSkipped('QUIQQER database has no usable super-user fixture.');
        }
    }

    private static function runAsRootUser(callable $Callback): mixed
    {
        $Users = QUI::getUsers();
        $RootUser = $Users->get(QUI::conf('globals', 'rootuser'));
        $SessionProperty = new ReflectionProperty($Users, 'Session');
        $PermissionProperty = new ReflectionProperty(Permission::class, 'User');
        $RequestUserProperty = new ReflectionProperty(Server::class, 'RequestUser');
        $PreviousSessionUser = $SessionProperty->getValue($Users);
        $PreviousPermissionUser = $PermissionProperty->getValue();
        $PreviousRequestUser = $RequestUserProperty->getValue();

        $SessionProperty->setValue($Users, $RootUser);
        $PermissionProperty->setValue(null, $RootUser);
        $RequestUserProperty->setValue(null, $RootUser);

        try {
            return $Callback();
        } finally {
            $SessionProperty->setValue($Users, $PreviousSessionUser);
            $PermissionProperty->setValue(null, $PreviousPermissionUser);
            $RequestUserProperty->setValue(null, $PreviousRequestUser);
        }
    }
}
