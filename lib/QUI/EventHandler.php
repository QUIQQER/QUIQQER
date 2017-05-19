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
            };
       
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
     * @param string                    $newPass
     * @param string                    $oldPass
     */
    public static function onUserChangePassword(QUI\Interfaces\Users\User $User, $newPass, $oldPass)
    {
        $User->setAttribute('quiqqer.set.new.password', 0);
        $User->save(QUI::getUsers()->getSystemUser());
    }


    /**
     * Event: OnPackageUpdate
     *
     *
     * @param Package\Package $Package
     */
    public static function onPackageUpdate(QUI\Package\Package $Package)
    {
        if ($Package->getName() != "quiqqer/quiqqer") {
            return;
        }

        # Check if htaccess or nginx need to be recreated
        $webServerType = QUI::conf("webserver", "type");

        if ($webServerType == "apache2.4" || $webServerType == "apache2.2") {
            $HtAccess = new QUI\System\Console\Tools\Htaccess();
            if ($HtAccess->hasModifications()) {
                $HtAccess->execute();
                QUI\System\Log::addInfo("Found changes in .htaccess. Recreating the htaccess file.");
            }
        }

        if ($webServerType == "nginx") {
            $Nginx = new QUI\System\Console\Tools\Nginx();
            if ($Nginx->hasModifications()) {
                $Nginx->execute();
                QUI\System\Log::addInfo("Found changes in nginx.conf . Recreating the nginx.conf file.");
            }
        }
    }
}
