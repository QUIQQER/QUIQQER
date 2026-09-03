<?php

namespace QUI\MCP\Project;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\Projects\Manager;
use Throwable;

class CreateProject extends AbstractProjectLifecycleTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $name,
                string $defaultLanguage,
                array $languages = [],
                string $template = '',
                bool $applyDemoData = false,
                ?string $demoDataSet = null
            ): CallToolResult | array {
                try {
                    self::checkProjectAdministration();
                    self::assertProjectNameAvailable($name);
                    $languages = self::normalizeLanguages($defaultLanguage, $languages);
                    $template = self::validateTemplate($template);
                    $selectedDemoDataSet = null;

                    if ($applyDemoData) {
                        if ($template === '') {
                            throw new QUI\Exception('Demo data requires an installed project template.');
                        }

                        $sets = QUI\Utils\Project::getDemoDataSetsForTemplate($template);

                        if ($sets === []) {
                            throw new QUI\Exception('The selected template does not provide demo data.');
                        }

                        if ($demoDataSet !== null && !isset($sets[$demoDataSet])) {
                            throw new QUI\Exception('Unknown demo data set: ' . $demoDataSet);
                        }

                        if ($demoDataSet === null && count($sets) > 1) {
                            throw new QUI\Exception('A demoDataSet is required when a template provides multiple sets.');
                        }

                        $selectedDemoDataSet = $demoDataSet ?? (string)array_key_first($sets);
                    }

                    $Project = Manager::createProject(
                        $name,
                        strtolower(trim($defaultLanguage)),
                        $languages,
                        $template
                    );
                    $demoDataApplied = false;
                    $demoDataError = null;

                    if ($applyDemoData) {
                        try {
                            QUI\Utils\Project::applyDemoDataToProject(
                                $Project,
                                $template,
                                $selectedDemoDataSet
                            );
                            $demoDataApplied = true;
                        } catch (Throwable $Exception) {
                            $demoDataError = $Exception->getMessage();
                        }
                    }

                    return [
                        'created' => true,
                        'project' => self::parseProject($Project),
                        'demoDataRequested' => $applyDemoData,
                        'demoDataApplied' => $demoDataApplied,
                        'demoDataSet' => $selectedDemoDataSet,
                        'demoDataError' => $demoDataError
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_projects_create',
            description: 'Creates a QUIQQER project with installed languages and an optional template/demo data set.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['name', 'defaultLanguage'],
                'properties' => [
                    'name' => ['type' => 'string', 'minLength' => 3],
                    'defaultLanguage' => ['type' => 'string', 'pattern' => '^[a-z]{2}$'],
                    'languages' => [
                        'type' => 'array',
                        'uniqueItems' => true,
                        'items' => ['type' => 'string', 'pattern' => '^[a-z]{2}$'],
                        'default' => []
                    ],
                    'template' => ['type' => 'string', 'default' => ''],
                    'applyDemoData' => ['type' => 'boolean', 'default' => false],
                    'demoDataSet' => ['type' => ['string', 'null']]
                ]
            ]
        );
    }
}
