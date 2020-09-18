<?php

/**
 * This file contains QUI\Utils\Project
 */

namespace QUI\Utils;

use QUI;
use QUI\Demodata\Parser\DemoDataParser;

/**
 * Class Project
 */
class Project
{
    /**
     * Create the default structure for a project
     *
     * @param QUI\Projects\Project $Project
     */
    public static function createDefaultStructure(QUI\Projects\Project $Project)
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
     * Create the default structure for a specific project language
     *
     * @param QUI\Projects\Project $Project
     * @throws QUI\Exception
     */
    protected static function createDefaultStructureForProjectLanguage(QUI\Projects\Project $Project)
    {
        $First = $Project->firstChild();
        $First = $First->getEdit();

        if (!$First->getAttribute('layout')
            || $First->getAttribute('layout') === ''
        ) {
            $First->setAttribute('layout', 'layout/startpage');
            $First->save();
        }

        // Search
        $searchType = 'quiqqer/sitetypes:types/search';

        try {
            QUI::getPackage('quiqqer/search');
            $searchType = 'quiqqer/sitetypes:types/search';
        } catch (QUI\Exception $Exception) {
        }

        $search = $Project->getSitesIds([
            'where' => [
                'active' => -1,
                'type'   => $searchType
            ],
            'limit' => 1
        ]);

        if (empty($search)) {
            try {
                $searchId = $First->createChild([
                    'name'  => self::parseForUrl(
                        'quiqqer/quiqqer',
                        'projects.defaultstructure.search.name',
                        $Project
                    ),
                    'title' => self::parseForUrl(
                        'quiqqer/quiqqer',
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
                'type'   => 'quiqqer/sitetypes:types/legalnotes'
            ],
            'limit' => 1
        ]);

        if (empty($legalNotes)) {
            try {
                $legalNoteId = $First->createChild([
                    'name'  => self::parseForUrl(
                        'quiqqer/quiqqer',
                        'projects.defaultstructure.legalnotes.name',
                        $Project
                    ),
                    'title' => self::parseForUrl(
                        'quiqqer/quiqqer',
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
                'type'   => 'quiqqer/sitetypes:types/generalTermsAndConditions'
            ],
            'limit' => 1
        ]);

        if (empty($generalTermsAndConditions)) {
            try {
                $generalTermsAndConditionsId = $First->createChild([
                    'name'  => self::parseForUrl(
                        'quiqqer/quiqqer',
                        'projects.defaultstructure.generalTermsAndConditions.name',
                        $Project
                    ),
                    'title' => self::parseForUrl(
                        'quiqqer/quiqqer',
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
                'type'   => 'quiqqer/sitetypes:types/privacypolicy'
            ],
            'limit' => 1
        ]);

        if (empty($privacyPolicy)) {
            try {
                $privacyPolicyId = $First->createChild([
                    'name'  => self::parseForUrl(
                        'quiqqer/quiqqer',
                        'projects.defaultstructure.privacypolicy.name',
                        $Project
                    ),
                    'title' => self::parseForUrl(
                        'quiqqer/quiqqer',
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
     * Apply demo data to a project
     *
     * @param QUI\Projects\Project $Project
     * @param string $templateName
     *
     * @throws QUI\Exception
     */
    public static function applyDemoDataToProject(QUI\Projects\Project $Project, $templateName)
    {
        $TemplatePackage = QUI::getPackageManager()->getInstalledPackage($templateName);
        $Parser          = new DemoDataParser();

        $demoDataArray = [];

        if (\file_exists($TemplatePackage->getDir().'demodata.xml')) {
            $demoDataArray = $Parser->parse($TemplatePackage);
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
     * Parse a locale string that no url error exists
     *
     * @param string $group
     * @param string $var
     * @param QUI\Projects\Project $Project
     *
     * @return array|string
     */
    protected static function parseForUrl($group, $var, QUI\Projects\Project $Project)
    {
        // quiqqer/quiqqer#825
        $language = $Project->getLang();

        // import
        QUI::getLocale()->getByLang($language, $group, $var);

        if (!QUI::getLocale()->exists($language)) {
            // check if we can import it
            QUI::getLocale()->getByLang($language, $group, $var);

            if (!QUI::getLocale()->exists($language)) {
                $language = 'en';

                // import
                QUI::getLocale()->getByLang($language, $group, $var);
            }

            // if en doesn't exists, we use the first available language
            if (!QUI::getLocale()->exists($language)) {
                $language = QUI::availableLanguages()[0];
            }
        }

        $str = QUI::getLocale()->getByLang($language, $group, $var);
        $str = QUI\Projects\Site\Utils::clearUrl($str, $Project);

        return $str;
    }

    /**
     * Validates the projects name
     *
     * @param $projectName
     *
     * @return bool
     * @throws QUI\Exception
     */
    public static function validateProjectName($projectName)
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

        if (\preg_match("@[-.,:;#`!§$%&/?<>\=\'\" ]@", $projectName)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.project.not.allowed.signs',
                    [
                        'signs' => \implode(' ', $forbiddenSigns)
                    ]
                ),
                802
            );
        }

        return true;
    }
}
