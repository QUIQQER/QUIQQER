<?php

/**
 * This file contains the \QUI\Projects\Trash
 */

namespace QUI\Projects;

use QUI;
use QUI\Database\Exception;

use function is_array;

/**
 * Trash from a Project
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Trash extends QUI\QDOM implements QUI\Interfaces\Projects\Trash
{
    /**
     * The Project of the trash
     */
    protected ?Project $Project = null;

    public function __construct(Project $Project)
    {
        $this->Project = $Project;
    }

    /**
     * Get Sites from Trash
     *
     * @param array $params - optional
     *                      - order
     *                      - sort
     *
     * - max
     * - page
     *
     * @return array
     * @throws Exception
     */
    public function getList(array $params = []): array
    {
        // create grid
        $Grid = new QUI\Utils\Grid();

        $_params = $Grid->parseDBParams($params);

        $_params['where'] = [
            'active' => -1,
            'deleted' => 1
        ];


        /**
         * Order and Sort
         */
        if (isset($params['order'])) {
            $_params['order'] = match ($params['order']) {
                'name', 'title', 'type' => $params['order'],
                default => 'id',
            };
        }

        if (isset($params['sort'])) {
            $_params['order'] = match ($params['sort']) {
                'ASC' => $_params['order'] . ' ASC',
                default => $_params['order'] . ' DESC',
            };
        }

        /**
         * Creating result
         */
        $result = [];
        $sites = $this->Project->getSites($_params);

        foreach ($sites as $Site) {
            try {
                /* @var $Site Site */
                $result[] = [
                    'icon' => URL_BIN_DIR . '16x16/page.png',
                    'name' => $Site->getAttribute('name'),
                    'title' => $Site->getAttribute('title'),
                    'type' => $Site->getAttribute('type'),
                    'id' => $Site->getId(),
                    'path' => $Site->getUrlRewritten()
                ];
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        $total = $this->Project->getSites([
            'where' => [
                'active' => -1,
                'deleted' => 1
            ],
            'count' => true
        ]);

        return $Grid->parseResult($result, (int)$total);
    }

    /**
     * Clear complete trash
     * @throws QUI\Exception
     */
    public function clear(): void
    {
        $ids = $this->Project->getSitesIds([
            'where' => [
                'deleted' => 1,
                'active' => -1
            ]
        ]);

        foreach ($ids as $data) {
            $Site = new Site\Edit($this->Project, (int)$data['id']);
            $Site->destroy();
        }
    }

    /**
     * Zerstört die gewünschten Seiten im Trash
     *
     * @throws QUI\Exception
     */
    public function destroy(array $ids = []): void
    {
        foreach ($ids as $id) {
            $Site = new Site\Edit($this->Project, (int)$id);
            $Site->destroy();
        }
    }

    /**
     * Stellt die gewünschten Seiten wieder her
     *
     * @throws QUI\Exception
     */
    public function restore(Project $Project, array $ids, int $parentid): void
    {
        $Parent = new Site\Edit($Project, $parentid);

        foreach ($ids as $id) {
            $Site = new Site\Edit($Project, $id);

            $Site->restore();
            $Site->move($Parent->getId());
            $Site->deactivate();
        }
    }
}
