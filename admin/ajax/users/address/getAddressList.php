<?php

/**
 * Return addresses by ids
 *
 * @param array|string $ids - list of ids
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_users_address_getAddressList',
    function ($ids) {
        $ids = json_decode($ids, true);
        $list = [];

        foreach ($ids as $id) {
            $result = QUI::getDataBase()->fetch([
                'select' => ['id', 'uid'],
                'from' => QUI\Users\Manager::tableAddress(),
                'where_or' => [
                    'id' => $id,
                    'uuid' => $id
                ],
                'limit' => 1
            ]);

            if (!isset($result[0])) {
                continue;
            }

            try {
                $uid = $result[0]['uid'];
                $User = QUI::getUsers()->get($uid);
                $Address = $User->getAddress($id);

                $attributes = $Address->getAttributes();
                $attributes['text'] = $Address->getText();
                $attributes['id'] = $Address->getUUID();
                $attributes['uuid'] = $Address->getUUID();

                $list[] = $attributes;
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeDebugException($Exception);
            }
        }

        return $list;
    },
    ['ids'],
    'Permission::checkAdminUser'
);
