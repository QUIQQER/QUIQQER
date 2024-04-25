<?php

/**
 * This file contains the \QUI\Projects\Site\DB
 */

namespace QUI\Projects\Site;

use Exception;
use QUI;

/**
 * This object is only used to if a Site is requested and the current user
 * has no permission to view this Site
 *
 * @author www.pcsg.de (Patrick Müller)
 * @licence For copyright and license information, please view the /README.md
 */
class PermissionDenied extends QUI\Projects\Site
{
    /**
     * constructor
     *
     * @param \QUI\Projects\Project $Project
     * @param integer $id - Site ID
     *
     * @throws QUI\Exception
     * @throws Exception
     */
    public function __construct(QUI\Projects\Project $Project, int $id)
    {
        $this->TABLE = $Project->table();
        $this->RELTABLE = $Project->table() . '_relations';
        $this->RELLANGTABLE = $Project->getAttribute('name') . '_multilingual';

        if (empty($id)) {
            throw new QUI\Exception('Site Error; No ID given:' . $id, 400);
        }

        $this->id = $id;
        $this->Events = new QUI\Events\Event();
        $this->Project = $Project;

        // Get data from start page
        $this->refresh();

        // onInit event
        $this->Events->fireEvent('init', [$this]);
        QUI::getEvents()->fireEvent('siteInit', [$this]);
    }

    /**
     * Get data from start Site (ID: 1)
     *
     * @return void
     * @throws Exception
     */
    public function refresh()
    {
        try {
            $result = QUI::getDataBase()->fetch([
                'from' => $this->TABLE,
                'where' => [
                    'id' => 1
                ],
                'limit' => '1'
            ]);
        } catch (Exception) {
            return;
        }

        $this->setAttributes($result[0]);

        // content
        if (QUI::getUserBySession()->getId()) {
            $User = QUI::getUserBySession();

            // eingeloggt, aber keine permission -> hinweis
            $message = QUI::getLocale()->get('quiqqer/quiqqer', 'site.permission.denied.for.logged.in.users.message', [
                'username' => $User->getUsername(),
                'name' => $User->getName()
            ]);

            $button = '<a href="?logout=1" class="btn qui-button">' .
                QUI::getLocale()->get('quiqqer/quiqqer', 'logout') .
                '</a>';

            $this->setAttribute(
                'content',
                $message . '<br /><br />' . $button
            );
        } else {
            $isFrontendUsersInstalled = QUI::getPackageManager()->isInstalled('quiqqer/frontend-users');

            // nicht eingeloggt, login anbieten
            if (!$isFrontendUsersInstalled) {
                $Login = new QUI\Users\Controls\Login();
                $this->setAttribute('content', $Login->create());
            } else {
                $this->setAttribute(
                    'content',
                    '<div data-qui="package/quiqqer/frontend-users/bin/frontend/controls/login/Login"
                           data-qui-options-redirect="0"
                           data-qui-options-reload="1"
                    ></div>'
                );
            }
        }
    }

    /**
     * @inheritdoc
     * @return QUI\Projects\Site
     */
    public function load($plugin = false): QUI\Projects\Site
    {
        return $this;
    }
}
