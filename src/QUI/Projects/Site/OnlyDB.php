<?php

/**
 * This file contains the \QUI\Projects\Site\DB
 */

namespace QUI\Projects\Site;

use QUI;
use QUI\Exception;
use QUI\Projects\Project;

use function json_decode;

/**
 * This object is only used to get data purely from the DataBase
 * Without performing file system operations (cache etc.)
 *
 * @author www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class OnlyDB extends QUI\Projects\Site
{
    /**
     * constructor
     *
     * @param Project $Project
     * @param integer $id - Site ID
     *
     * @throws QUI\Exception
     */
    public function __construct(Project $Project, int $id)
    {
        $this->TABLE = $Project->table();
        $this->RELTABLE = $Project->table() . '_relations';
        $this->RELLANGTABLE = $Project->getAttribute('name') . '_multilingual';

        if (empty($id)) {
            throw new QUI\Exception('Site Error; No ID given:' . $id, 400);
        }

        $this->id = $id;
        $this->Events = new QUI\Events\Event();

        // Daten aus der DB hohlen
        $this->refresh();


        // onInit event
        $this->Events->fireEvent('init', [$this]);
        QUI::getEvents()->fireEvent('siteInit', [$this]);
    }

    /**
     * Hohlt sich die Daten frisch us der DB
     * @throws Exception
     */
    public function refresh(): void
    {
        $result = QUI::getDataBase()->fetch([
            'from' => $this->TABLE,
            'where' => [
                'id' => $this->getId()
            ],
            'limit' => '1'
        ]);

        if (!isset($result[0])) {
            throw new QUI\Exception('Site not exist', 404);
        }

        // Verknüpfung hohlen
        if ($this->getId() != 1) {
            $relresult = QUI::getDataBase()->fetch([
                'from' => $this->RELTABLE,
                'where' => [
                    'child' => $this->getId()
                ]
            ]);

            if (isset($relresult[0])) {
                foreach ($relresult as $entry) {
                    if (!isset($entry['oparent'])) {
                        continue;
                    }

                    $this->LINKED_PARENT = $entry['oparent'];
                }
            }
        }

        /* deprecated */
        if (isset($result[0]['extra'])) {
            $extra = json_decode($result[0]['extra'], true);

            foreach ($extra as $key => $value) {
                $this->setAttribute($key, $value);
            }

            unset($result[0]['extra']);
        }

        $this->setAttributes($result[0]);
    }
}
