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
    function ($template, $package, $params = '') {
        $Engine = QUI::getTemplateManager()->getEngine(true);

        if (isset($package) && !empty($package)) {
            $template = OPT_DIR.$package.'/'.str_replace('_', '/', $template).'.html';
        } else {
            $dir      = SYS_DIR.'template/';
            $template = $dir.str_replace('_', '/', $template).'.html';
        }

        if (!file_exists($template)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.template.not.found'
                )
            );
        }

        if (!empty($params)) {
            $params = json_decode($params, true);
        }

        $Engine->assign([
            'QUI'    => new QUI(),
            'params' => $params
        ]);

        return $Engine->fetch($template);
    },
    ['template', 'package', 'params'],
    'Permission::checkAdminUser'
);
