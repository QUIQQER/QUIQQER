<?php

/**
 * This file contains \QUI\Intranet\EventHandler
 */

namespace QUI;

use QUI;

/**
 * Intranet
 *
 * @author www.pcsg.de
 */
class EventHandler
{
    /**
     * event on onAdminLoadFooter
     */
    public static function onAdminLoadFooter()
    {
        $User = QUI::getUserBySession();

        if (!$User->getAttribute('quiqqer.set.new.password')) {
            return;
        }

        echo "<script>
            var openChangePasswordWindow = function() {
                require([
                    'controls/users/password/Window',
                    'Locale'
                ], function(Password, QUILocale) {
                    new Password({
                        mustChange: true,
                        message: QUILocale.get('quiqqer/quiqqer', 'message.set.new.password')
                    }).open();
                });
            }
       
            require(['Locale'], function(QUILocale) {
                if (!QUILocale.exists('quiqqer/quiqqer', 'message.set.new.password')) {
                    (function() {
                        openChangePasswordWindow();
                    }).delay(2000);
                    return;
                }
                
                openChangePasswordWindow();
            });
    
        </script>";
    }

    /**
     * @param QUI\Interfaces\Users\User $User
     * @param string $newPass
     * @param string $oldPass
     */
    public static function onUserChangePassword(QUI\Interfaces\Users\User $User, $newPass, $oldPass)
    {
        $User->setAttribute('quiqqer.set.new.password', 0);
        $User->save(QUI::getUsers()->getSystemUser());
    }

    /**
     * @param Package\Package $Package
     */
    public function onPackageSetup(QUI\Package\Package $Package)
    {
        // create auth provider as user permissions
        $authProviders = $Package->getProvider('auth');

        if (!empty($authProviders)) {
            return;
        }

        // <permission name="quiqqer.auth.AUTH.canUser" type="bool" />

        foreach ($authProviders as $authProvider) {
            $permissionName = 'quiqqer.auth.' . str_replace('\\', '', $authProvider) . '.canUser';

        }
    }
}
