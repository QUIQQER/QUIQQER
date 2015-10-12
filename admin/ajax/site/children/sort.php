<?php

/**
 * Sort the children
 *
 * @param String  $project - Project name
 * @param Integer $ids     - children ids
 * @param Integer $from    - Sheet number
 */
function ajax_site_children_sort($project, $ids, $from)
{
    $Project = \QUI::getProjectManager()->decode($project);
    $ids = json_decode($ids, true);

    $from = (int)$from;

    foreach ($ids as $id) {
        $from = $from + 1;
        $Child = $Project->get($id);

        $Child->setAttribute('order_field', $from);
        $Child->save();
    }

    QUI::getMessagesHandler()->clear();

    QUI::getMessagesHandler()->addSuccess(
        QUI::getLocale()->get(
            'quiqqer/system',
            'message.site.save.sort.success',
            array('ids' => implode(',', $ids))
        )
    );
}

QUI::$Ajax->register(
    'ajax_site_children_sort',
    array('project', 'ids', 'from')
);
