<?php

/**
 * Return a template
 *
 * @param string $template
 * @param string $package
 * @param string $params
 *
 * @return string
 *
 * @throws QUI\Exception
 */

QUI::$Ajax->registerFunction(
    'ajax_template_get',
    static function ($template, $package, $params = '') {
        $current = QUI::getLocale()->getCurrent();
        $Engine = QUI::getTemplateManager()->getEngine(true);

        if (!empty($package)) {
            QUI::getPackage($package); // check if package exists

            $template = OPT_DIR . $package . '/' . str_replace('_', '/', $template) . '.html';
        } else {
            $dir = SYS_DIR . 'template/';
            $template = $dir . str_replace('_', '/', $template) . '.html';
        }

        $template = realpath($template);

        if (!$template || !file_exists($template)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.template.not.found'
                )
            );
        }

        if (!str_starts_with($template, CMS_DIR)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.template.not.found'
                )
            );
        }

        if (!empty($params)) {
            $params = json_decode($params, true);
        }

        $QUI = new QUI();
        $QUI::getLocale()->setCurrent($current);

        $Engine->assign([
            'QUI' => $QUI,
            'params' => $params
        ]);

        return $Engine->fetch($template);
    },
    ['template', 'package', 'params'],
    'Permission::checkAdminUser'
);
