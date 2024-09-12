<?php

/**
 * Creates a child
 *
 * @param string $project - Project name
 * @param integer $id - Parent ID
 * @param string $attributes - JSON Array, child attributes
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_site_children_create',
    static function ($project, $id, $attributes): array {
        $attributes = json_decode($attributes, true);

        if (empty($attributes['name']) && !empty($attributes['title'])) {
            $attributes['name'] = QUI\Projects\Site\Utils::clearUrl($attributes['title']);
        }

        $Project = QUI::getProjectManager()->decode($project);
        $Site = new QUI\Projects\Site\Edit($Project, (int)$id);
        $childId = $Site->createChild($attributes);

        $Child = new QUI\Projects\Site\Edit($Project, $childId);
        $Child->setAttributes($attributes);
        $Child->save();


        try {
            QUI::getEvents()->fireEvent('siteCreateChildEnd', [$Child]);
        } catch (\Exception $Exception) {
            QUI::getMessagesHandler()->addAttention(
                $Exception->getMessage()
            );
        }

        return $Child->getAttributes();
    },
    ['project', 'id', 'attributes']
);
