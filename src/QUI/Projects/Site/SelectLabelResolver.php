<?php

namespace QUI\Projects\Site;

use QUI;
use QUI\Projects\Project;
use Throwable;

final class SelectLabelResolver
{
    /**
     * @return array<string, array{value: string, kind: string, title: string, icon: string}>
     */
    public static function resolveEncoded(Project $Project, string $selectors): array
    {
        $selectors = json_decode($selectors, true);

        if (!is_array($selectors)) {
            return [];
        }

        return self::resolve($Project, $selectors);
    }

    /**
     * @param array<array-key, mixed> $selectors
     * @return array<string, array{value: string, kind: string, title: string, icon: string}>
     */
    public static function resolve(Project $Project, array $selectors): array
    {
        $Locale = QUI::getLocale();
        $PackageManager = QUI::getPackageManager();
        $result = [];

        foreach ($selectors as $selector) {
            if (!is_scalar($selector)) {
                continue;
            }

            $value = trim((string)$selector);

            if ($value === '' || isset($result[$value])) {
                continue;
            }

            $entry = [
                'value' => $value,
                'kind' => 'type',
                'title' => '',
                'icon' => 'fa fa-puzzle-piece'
            ];

            try {
                if (ctype_digit($value) && (int)$value > 0) {
                    $entry['kind'] = 'site';
                    $entry['icon'] = 'fa fa-file-o';
                    $Site = new Edit($Project, (int)$value);
                    $entry['title'] = (string)$Site->getAttribute('title');
                } elseif (preg_match('/^p([1-9][0-9]*)$/', $value, $matches)) {
                    $entry['kind'] = 'children';
                    $entry['icon'] = 'fa fa-sitemap';
                    $Site = new Edit($Project, (int)$matches[1]);
                    $entry['title'] = (string)$Site->getAttribute('title');
                } elseif (str_contains($value, '%')) {
                    $package = str_ends_with($value, ':%')
                        ? substr($value, 0, -2)
                        : '';

                    $entry['kind'] = 'typeWildcard';
                    $entry['title'] = $package !== '' ? $package : $value;
                    $entry['icon'] = 'fa fa-layer-group';

                    if ($package !== '' && $Locale->exists($package, 'package.title')) {
                        $entry['title'] = $Locale->get($package, 'package.title');
                    }
                } else {
                    $entry['title'] = $PackageManager->getSiteTypeName($value);
                    $icon = $PackageManager->getIconBySiteType($value);

                    if ($icon !== '') {
                        $entry['icon'] = $icon;
                    }
                }
            } catch (Throwable) {
            }

            $result[$value] = $entry;
        }

        return $result;
    }
}
