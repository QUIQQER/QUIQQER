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
        $Platform = QUI::getDataBaseConnection()->getDatabasePlatform();
        $QueryBuilder = QUI::getQueryBuilder();
        $result = $QueryBuilder
            ->select('*')
            ->from($Platform->quoteSingleIdentifier($this->TABLE))
            ->where($QueryBuilder->expr()->eq($Platform->quoteSingleIdentifier('id'), ':id'))
            ->setParameter('id', $this->getId())
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($result === false) {
            throw new QUI\Exception('Site not exist', 404);
        }

        // Verknüpfung hohlen
        if ($this->getId() != 1) {
            $RelationQueryBuilder = QUI::getQueryBuilder();
            $relresult = $RelationQueryBuilder
                ->select('*')
                ->from($Platform->quoteSingleIdentifier($this->RELTABLE))
                ->where($RelationQueryBuilder->expr()->eq($Platform->quoteSingleIdentifier('child'), ':child'))
                ->setParameter('child', $this->getId())
                ->executeQuery()
                ->fetchAllAssociative();

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
        if (isset($result['extra'])) {
            $extra = json_decode($result['extra'], true);

            foreach ($extra as $key => $value) {
                $this->setAttribute($key, $value);
            }

            unset($result['extra']);
        }

        $this->setAttributes($result);
    }
}
