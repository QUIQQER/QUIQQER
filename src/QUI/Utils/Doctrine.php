<?php

namespace QUI\Utils;

use Doctrine\DBAL\Query\QueryBuilder;

use function explode;
use function is_array;
use function is_string;

class Doctrine
{
    public static function parseDbArrayToQueryBuilder(QueryBuilder $query, array $params): QueryBuilder
    {
        if (!empty($params['update'])) {
            $update = $params['update'];
            $up = 0;

            foreach ($update as $field => $value) {
                $query->set($field, ':up' . $up)->setParameter('up' . $up, $value);
                $up++;
            }
        }

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
                $query->setFirstResult((int)$limit[0]);
            }

            if (!empty($limit[1])) {
                $query->setMaxResults((int)$limit[1]);
            }
        }

        return $query;
    }
}
