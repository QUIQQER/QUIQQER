<?php

namespace QUI\Utils;

use Doctrine\DBAL\Query\QueryBuilder;

use function explode;
use function is_array;
use function is_string;

class Doctrine
{
    public static function quoteIdentifier(string $identifier): string
    {
        return \QUI::getDataBaseConnection()
            ->getDatabasePlatform()
            ->quoteSingleIdentifier($identifier);
    }

    /**
     * @param array<string, mixed> $params
     */
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
                $query->andWhere($where);
            }

            if (is_array($where)) {
                $wp = 0;

                foreach ($where as $key => $value) {
                    $parameter = 'wp' . $wp;
                    $wp++;

                    if (is_array($value) && isset($value['type'], $value['value'])) {
                        $type = strtoupper((string)$value['type']);
                        $value = $value['value'];

                        if ($type === 'NOT') {
                            $query->andWhere($key . ' <> :' . $parameter)->setParameter($parameter, $value);
                            continue;
                        }

                        $query->andWhere($key . ' ' . $type . ' :' . $parameter)->setParameter($parameter, $value);
                        continue;
                    }

                    if (!is_array($value)) {
                        $query->andWhere($key . ' = :' . $parameter)->setParameter($parameter, $value);
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
            $limit = explode(',', (string)$params['limit'], 2);

            if (isset($limit[1])) {
                $query->setFirstResult((int)$limit[0]);
                $query->setMaxResults((int)$limit[1]);
            } else {
                $query->setMaxResults((int)$limit[0]);
            }
        }

        return $query;
    }
}
