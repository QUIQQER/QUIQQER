<?php

namespace QUI\Utils;

use Doctrine\DBAL\Query\QueryBuilder;

use function explode;
use function filter_var;
use function is_array;
use function is_int;
use function is_string;
use function trim;

use const FILTER_VALIDATE_INT;

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

        self::applyLimit($query, $params['limit'] ?? null);

        return $query;
    }

    /**
     * Applies a positive limit or a non-negative offset with a positive limit.
     */
    public static function applyLimit(QueryBuilder $query, mixed $value): QueryBuilder
    {
        if (!is_int($value) && !is_string($value)) {
            return $query;
        }

        $limit = explode(',', (string)$value, 2);
        $maxResults = filter_var(
            trim($limit[1] ?? $limit[0]),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($maxResults === false) {
            return $query;
        }

        if (!isset($limit[1])) {
            return $query->setMaxResults($maxResults);
        }

        $offset = filter_var(
            trim($limit[0]),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]]
        );

        if ($offset === false) {
            return $query;
        }

        return $query
            ->setFirstResult($offset)
            ->setMaxResults($maxResults);
    }
}
