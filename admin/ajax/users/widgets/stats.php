<?php

/**
 * user stats for administrations
 *
 * @return String
 */
QUI::$Ajax->registerFunction(
    'ajax_users_widgets_stats',
    function () {
        $list = QUI::getUsers()->getAllUsers();

        $active   = 0;
        $inactive = 0;
        $deleted  = 0;

        foreach ($list as $entry) {
            switch ((int)$entry['active']) {
                case 1:
                    $active++;
                    break;

                case 0:
                    $inactive++;
                    break;

                case -1:
                    $deleted++;
                    break;
            }
        }

        $table = '<table class="data-table">' .
                 '<thead>' .
                 '   <tr class="odd">' .
                 '       <th colspan="2">Benutzer Statistiken</th>' .
                 '   </tr>' .
                 '</thead>' .
                 '<tbody>' .
                 '   <tr class="even">' .
                 '       <td>Gesamt Benutzer</td>' .
                 '     <td>' . count($list) . '</td>' .
                 '   </tr>' .
                 '   <tr class="odd">' .
                 '       <td>Aktiv</td>' .
                 '       <td>' . $active . '</td>' .
                 '   </tr>' .
                 '   <tr class="even">' .
                 '       <td>Inaktiv</td>' .
                 '       <td>' . $inactive . '</td>' .
                 '   </tr>' .
                 '   <tr class="odd">' .
                 '       <td>Gelöscht</td>' .
                 '       <td>' . $deleted . '</td>' .
                 '   </tr>' .
                 '</tbody>' .
                 '</table>';

        return $table;
    },
    false,
    'Permission::checkSU'
);
