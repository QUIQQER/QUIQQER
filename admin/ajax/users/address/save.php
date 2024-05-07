<?php

/**
 * Saves the address
 *
 * @param string $uid - User ID
 * @param string $aid - Address ID
 * @param string $data - JSON Array
 *
 * @return integer
 */

QUI::$Ajax->registerFunction(
    'ajax_users_address_save',
    function ($uid, $aid, $data) {
        $data = json_decode($data, true);

        if (!isset($uid) || !$uid) {
            $result = QUI::getDataBase()->fetch([
                'select' => ['id', 'uid'],
                'from' => QUI\Users\Manager::tableAddress(),
                'where_or' => [
                    'id' => $aid,
                    'uuid' => $aid
                ],
                'limit' => 1
            ]);

            if (!isset($result[0])) {
                throw new QUI\Users\Exception(
                    QUI::getLocale()->get(
                        'quiqqer/core',
                        'exception.lib.user.address.not.found',
                        [
                            'addressId' => $aid,
                            'userId' => $uid
                        ]
                    )
                );
            }

            $uid = $result[0]['uid'];
        }

        $User = QUI::getUsers()->get($uid);

        try {
            $Address = $User->getAddress($aid);
        } catch (QUI\Exception) {
            $Address = $User->addAddress($data);
        }

        $Address->clearMail();
        $Address->clearPhone();

        if (isset($data['mails']) && is_array($data['mails'])) {
            foreach ($data['mails'] as $mail) {
                $Address->addMail($mail);
            }
        }

        if (isset($data['phone']) && is_array($data['phone'])) {
            foreach ($data['phone'] as $phone) {
                $Address->addPhone($phone);
            }
        }

        if (isset($data['suffix'])) {
            $Address->setAddressSuffix($data['suffix']);
        }

        unset($data['mails']);
        unset($data['phone']);

        $Address->setAttributes($data);
        $Address->save();

        if (isset($data['standard']) && $data['standard'] === 1) {
            $User->setAttribute('address', $Address->getUUID());
            $User->save();
        }

        if ($Address->getUUID() === $User->getStandardAddress()->getUUID()) {
            $User->save();
        }

        return $Address->getUUID();
    },
    ['uid', 'aid', 'data'],
    ['Permission::checkAdminUser', 'quiqqer.admin.users.edit']
);
