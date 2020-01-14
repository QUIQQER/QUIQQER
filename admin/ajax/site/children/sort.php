<?php

/**
 * Sort the children
 *
 * @param string $project - Project name
 * @param integer $ids - children ids
 * @param integer $from - Sheet number
 */
QUI::$Ajax->registerFunction(
    'ajax_site_children_sort',
    function ($project, $parent, $ids, $from) {
        $Project = QUI::getProjectManager()->decode($project);
        $ids     = \json_decode($ids, true);
        $from    = (int)$from;

        // check permission
        $Parent = $Project->get($parent);
        $Parent->checkPermission('quiqqer.projects.site.edit');

        $childrenIds = $Parent->getChildrenIds();

        foreach ($ids as $id) {
            $from = $from + 1;

            if (!\in_array($id, $childrenIds)) {
                continue;
            }

            QUI::getDataBase()->update(
                $Project->table(),
                ['order_field' => $from],
                ['id' => $id]
            );
        }

        $Parent->save();

        QUI::getMessagesHandler()->clear();

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/system',
                'message.site.save.sort.success',
                ['ids' => \implode(',', $ids)]
            )
        );
    },
    ['project', 'parent', 'ids', 'from']
);
