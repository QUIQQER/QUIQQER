<?php

namespace QUI\Utils;

use Doctrine\DBAL\Query\QueryBuilder;

use function explode;
use function is_array;
use function is_string;

class Doctrine
{
    public static function parseDbArrayToQueryBuilder(QueryBuilder $query, $params): QueryBuilder
    {
        if (!empty($params['where'])) {
            $where = $params['where'];

            if (is_string($where)) {
                $query->where($where);
            }

            if (is_array($where)) {
                $wp = 0;

                foreach ($where as $key => $value) {
                    if (!is_array($value)) {
                        $query->where($key . ' = :wp' . $wp)->setParameter('wp' . $wp, $value);
                        $wp++;
                    }
                }
            }
        }

        if (!empty($params['order'])) {
            $order = explode(' ', $params['order']);

            $query->orderBy(
                $order[0],
                $order[1] ?? null
            );
        }

        if (isset($params['limit'])) {
            $limit = explode(',', $params['limit']);

            if (!empty($limit[0])) {
                $query->setFirstResult($limit[0]);
            }

            if (!empty($limit[1])) {
                $query->setMaxResults($limit[1]);
            }
        }

        return $query;
    }
}
