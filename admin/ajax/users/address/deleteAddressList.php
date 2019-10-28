<?php

/**
 * Return addresses by ids
 *
 * @param array|string $ids - list of ids
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_users_address_deleteAddressList',
    function ($ids) {
        $ids  = \json_decode($ids, true);
        $list = [];

        foreach ($ids as $id) {
            $result = QUI::getDataBase()->fetch([
                'select' => ['id', 'uid'],
                'from'   => QUI\Users\Manager::tableAddress(),
                'where'  => [
                    'id' => $id
                ],
                'limit'  => 1
            ]);

            if (!isset($result[0])) {
                continue;
            }

            try {
                $uid     = (int)$result[0]['uid'];
                $User    = QUI::getUsers()->get((int)$uid);
                $Address = $User->getAddress((int)$id);
                $Address->delete();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeDebugException($Exception);
            }
        }

        return $list;
    },
    ['ids'],
    'Permission::checkAdminUser'
);
