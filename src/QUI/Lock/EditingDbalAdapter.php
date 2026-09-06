<?php

namespace QUI\Lock;

use Doctrine\DBAL\Exception\TableExistsException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;

/** Provision isolated lease storage on its first read, without treating connection failures as cache misses. */
class EditingDbalAdapter extends DoctrineDbalAdapter
{
    /** @param list<string> $ids
     * @return iterable<string, mixed>
     */
    protected function doFetch(array $ids): iterable
    {
        try {
            yield from parent::doFetch($ids);
        } catch (TableNotFoundException) {
            try {
                $this->createTable();
            } catch (TableExistsException) {
                // Another request has already provisioned the table.
            }

            yield from parent::doFetch($ids);
        }
    }
}
