<?php

QUI::$Ajax->registerFunction(
    'ajax_user_setHasSeen2faInformation',
    static function (): void {
        $User = QUI::getUserBySession();

        if (QUI::getUsers()->isNobodyUser($User)) {
            try {
                $User = QUI::getUsers()->get(QUI::getSession()->get('uid'));
            } catch (QUI\Exception) {
                return;
            }
        }

        $User->setAttribute('quiqqer.has.seen.2fa.info', 1);
        $User->save(QUI::getUsers()->getSystemUser());
    }
);
