<?php

QUI::$Ajax->registerFunction(
    'ajax_user_getHasSeen2faInformation',
    static function (): int {
        $User = QUI::getUserBySession();

        if (QUI::getUsers()->isNobodyUser($User)) {
            try {
                $User = QUI::getUsers()->get(QUI::getSession()->get('uid'));
            } catch (QUI\Exception) {
                return 0;
            }
        }

        return (int)$User->getAttribute('quiqqer.has.seen.2fa.info');
    }
);
