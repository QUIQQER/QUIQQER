<?php

/**
 * This file contains QUI\Workspace\Search\ProviderInterface
 */
namespace QUI\Workspace\Search;

/**
 * Interface ProviderInterface
 * Interface for a DesktopSearch Provider
 * https://dev.quiqqer.com/quiqqer/quiqqer/wikis/DesktopSearch/
 *
 * @package QUI\Workspace\Search
 */
interface ProviderInterface
{
    /**
     * Build the cache
     *
     * @return mixed
     */
    public function buildCache();

    /**
     * Execute a search
     *
     * @param string $search
     * @param array $params
     * @return mixed
     */
    public function search($search, $params = array());

    /**
     * Return a search entry
     *
     * @param integer $id
     * @return mixed
     */
    public function getEntry($id);
}
