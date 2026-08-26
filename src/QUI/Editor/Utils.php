<?php

/**
 * This file contains \QUI\Editor\Utils
 */

namespace QUI\Editor;

use DOMElement;
use QUI\Utils\Text\XML;

use function trim;

/**
 * Editor utility helpers
 */
class Utils
{
    /**
     * Reads editor definitions including declared toolbars from an *.xml
     *
     * @param string $file
     * @return array<int, array{
     *     name: string,
     *     component: string,
     *     toolbars: array<int, array{
     *         name: string,
     *         src: string
     *     }>
     * }>
     */
    public static function getWysiwygEditorDefinitionsFromXml(string $file): array
    {
        $Dom = XML::getDomFromXml($file);
        $editors = $Dom->getElementsByTagName('editors');

        if (!$editors->length) {
            return [];
        }

        $Editors = $editors->item(0);

        if ($Editors === null) {
            return [];
        }

        $list = $Editors->getElementsByTagName('editor');

        if (!$list->length) {
            return [];
        }

        $result = [];

        for ($c = 0; $c < $list->length; $c++) {
            $Editor = $list->item($c);

            if ($Editor === null || $Editor->nodeName == '#text') {
                continue;
            }

            $definition = [
                'name' => trim($Editor->getAttribute('name')),
                'component' => trim($Editor->getAttribute('component')),
                'toolbars' => []
            ];

            if (empty($definition['component'])) {
                $definition['component'] = trim($Editor->getAttribute('package'));
            }

            foreach ($Editor->childNodes as $Child) {
                if ($Child->nodeName !== 'toolbars') {
                    continue;
                }

                foreach ($Child->childNodes as $Toolbar) {
                    if (!($Toolbar instanceof DOMElement) || $Toolbar->nodeName !== 'toolbar') {
                        continue;
                    }

                    $name = trim($Toolbar->getAttribute('name'));
                    $src = trim($Toolbar->getAttribute('src'));

                    if (empty($name) || empty($src)) {
                        continue;
                    }

                    $definition['toolbars'][] = [
                        'name' => $name,
                        'src' => $src
                    ];
                }
            }

            if (empty($definition['name'])) {
                $definition['name'] = trim($Editor->nodeValue ?? '');
            }

            $result[] = $definition;
        }

        return $result;
    }
}
