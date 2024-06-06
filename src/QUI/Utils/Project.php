<?php

/**
 * This file contains QUI\Utils\Project
 */

namespace QUI\Utils;

use QUI;
use QUI\Demodata\Parser\DemoDataParser;

use function file_exists;
use function implode;
use function preg_match;

class Project
{
    public static function createDefaultStructure(QUI\Projects\Project $Project): void
    {
        $languages = $Project->getLanguages();

        foreach ($languages as $language) {
            try {
                self::createDefaultStructureForProjectLanguage(
                    QUI::getProject($Project->getName(), $language)
                );
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeRecursive($Exception);
            }
        }
    }

    /**
     * @throws QUI\Exception
     */
    protected static function createDefaultStructureForProjectLanguage(QUI\Projects\Project $Project): void
    {
        $First = $Project->firstChild();
        $First = $First->getEdit();
        $layout = $First->getAttribute('layout');

        if (empty($layout)) {
            $First->setAttribute('layout', 'layout/startpage');
            $First->save();
        }

        // Search
        $searchType = 'quiqqer/sitetypes:types/search';

        if (QUI::getPackageManager()->isInstalled('quiqqer/search')) {
            $searchType = 'quiqqer/search:types/search';
        }

        $search = $Project->getSitesIds([
            'where' => [
                'active' => -1,
                'type' => $searchType
            ],
            'limit' => 1
        ]);

        if (empty($search)) {
            try {
                $searchId = $First->createChild([
                    'name' => self::parseForUrl(
                        'quiqqer/core',
                        'projects.defaultstructure.search.name',
                        $Project
                    ),
                    'title' => self::parseForUrl(
                        'quiqqer/core',
                        'projects.defaultstructure.search.title',
                        $Project
                    )
                ]);

                $Search = new QUI\Projects\Site\Edit($Project, $searchId);
                $Search->setAttribute('type', $searchType);
                $Search->save();
                $Search->activate();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        // Im print / legalnotes / Impressum
        $legalNotes = $Project->getSitesIds([
            'where' => [
                'active' => -1,
                'type' => 'quiqqer/sitetypes:types/legalnotes'
            ],
            'limit' => 1
        ]);

        if (empty($legalNotes)) {
            try {
                $legalNoteId = $First->createChild([
                    'name' => self::parseForUrl(
                        'quiqqer/core',
                        'projects.defaultstructure.legalnotes.name',
                        $Project
                    ),
                    'title' => self::parseForUrl(
                        'quiqqer/core',
                        'projects.defaultstructure.legalnotes.name',
                        $Project
                    )
                ]);

                $Legal = new QUI\Projects\Site\Edit($Project, $legalNoteId);
                $Legal->setAttribute('type', 'quiqqer/sitetypes:types/legalnotes');
                $Legal->save();
                $Legal->activate();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        // AGB / generalTermsAndConditions
        $generalTermsAndConditions = $Project->getSitesIds([
            'where' => [
                'active' => -1,
                'type' => 'quiqqer/sitetypes:types/generalTermsAndConditions'
            ],
            'limit' => 1
        ]);

        if (empty($generalTermsAndConditions)) {
            try {
                $generalTermsAndConditionsId = $First->createChild([
                    'name' => self::parseForUrl(
                        'quiqqer/core',
                        'projects.defaultstructure.generalTermsAndConditions.name',
                        $Project
                    ),
                    'title' => self::parseForUrl(
                        'quiqqer/core',
                        'projects.defaultstructure.generalTermsAndConditions.name',
                        $Project
                    )
                ]);

                $GTC = new QUI\Projects\Site\Edit($Project, $generalTermsAndConditionsId);
                $GTC->setAttribute('type', 'quiqqer/sitetypes:types/generalTermsAndConditions');
                $GTC->save();
                $GTC->activate();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        // Datenschutzerklärung / privacypolicy
        $privacyPolicy = $Project->getSitesIds([
            'where' => [
                'active' => -1,
                'type' => 'quiqqer/sitetypes:types/privacypolicy'
            ],
            'limit' => 1
        ]);

        if (empty($privacyPolicy)) {
            try {
                $privacyPolicyId = $First->createChild([
                    'name' => self::parseForUrl(
                        'quiqqer/core',
                        'projects.defaultstructure.privacypolicy.name',
                        $Project
                    ),
                    'title' => self::parseForUrl(
                        'quiqqer/core',
                        'projects.defaultstructure.privacypolicy.name',
                        $Project
                    )
                ]);

                $Legal = new QUI\Projects\Site\Edit($Project, $privacyPolicyId);
                $Legal->setAttribute('type', 'quiqqer/sitetypes:types/privacypolicy');
                $Legal->save();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }
    }

    /**
     * Parse a locale string that no url error exists
     */
    protected static function parseForUrl(string $group, string $var, QUI\Projects\Project $Project): string
    {
        // quiqqer/core#825
        $language = $Project->getLang();

        // import
        QUI::getLocale()->getByLang($language, $group, $var);

        if (!QUI::getLocale()->exists($language)) {
            // try import
            QUI::getLocale()->getByLang($language, $group, $var);
        }

        if (!QUI::getLocale()->exists($language)) {
            $language = 'en';
            QUI::getLocale()->getByLang($language, $group, $var);
        }

        // if en doesn't exist, we use the first available language
        if (!QUI::getLocale()->exists($language)) {
            $language = QUI::availableLanguages()[0];
        }

        $str = QUI::getLocale()->getByLang($language, $group, $var);

        return QUI\Projects\Site\Utils::clearUrl($str, $Project);
    }

    /**
     * @throws QUI\Exception
     */
    public static function applyDemoDataToProject(QUI\Projects\Project $Project, string $templateName): void
    {
        $TemplatePackage = QUI::getPackageManager()->getInstalledPackage($templateName);
        $Parser = new DemoDataParser();

        $demoDataArray = [];

        if (file_exists($TemplatePackage->getDir() . 'demodata.xml')) {
            $demoDataArray = $Parser->parse($TemplatePackage, $Project);
        }

        if (empty($demoDataArray)) {
            throw new QUI\Demodata\Exceptions\UnsupportedException([
                'quiqqer/demodata',
                'exception.template.unsupported'
            ]);
        }

        $DemoData = new QUI\Demodata\DemoData();
        $DemoData->apply($Project, $demoDataArray);
    }

    /**
     * @throws QUI\Exception
     */
    public static function validateProjectName($projectName): bool
    {
        $forbiddenSigns = [
            '-',
            '.',
            ',',
            ':',
            ';',
            '#',
            '`',
            '!',
            '§',
            '$',
            '%',
            '&',
            '/',
            '?',
            '<',
            '>',
            '=',
            '\'',
            '"'
        ];

        if (preg_match("@[-.,:;#`!§$%&/?<>\=\'\" ]@", $projectName)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.project.not.allowed.signs',
                    [
                        'signs' => implode(' ', $forbiddenSigns)
                    ]
                ),
                802
            );
        }

        return true;
    }
}
