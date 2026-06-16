<?php

/**
 * Return mail queue entries for grid output
 */

use QUI\Mail\Queue;

QUI::getAjax()->registerFunction(
    'ajax_system_mailqueue_list',
    static function ($params): array {
        $formatAddressList = static function ($json): string {
            if (is_string($json)) {
                $json = json_decode($json, true);
            }

            if (!is_array($json)) {
                return '-';
            }

            $result = [];

            foreach ($json as $entry) {
                if (is_array($entry) && isset($entry[0])) {
                    if (!empty($entry[1])) {
                        $result[] = $entry[1] . ' <' . $entry[0] . '>';
                        continue;
                    }

                    $result[] = $entry[0];
                    continue;
                }

                if (is_string($entry) && trim($entry) !== '') {
                    $result[] = trim($entry);
                }
            }

            if (empty($result)) {
                return '-';
            }

            return implode(', ', $result);
        };

        $params = json_decode($params, true);

        if (!is_array($params)) {
            $params = [];
        }

        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['perPage']) ? (int)$params['perPage'] : 20;
        $sortOn = isset($params['sortOn']) ? (string)$params['sortOn'] : 'lastsend';
        $sortBy = isset($params['sortBy']) ? (string)$params['sortBy'] : 'DESC';
        $search = isset($params['search']) ? trim((string)$params['search']) : '';

        if ($page < 1) {
            $page = 1;
        }

        if ($limit < 1) {
            $limit = 20;
        }

        $allowedSortFields = ['id', 'subject', 'lastsend', 'retry', 'status'];

        if (!in_array($sortOn, $allowedSortFields, true)) {
            $sortOn = 'lastsend';
        }

        $sortBy = strtoupper($sortBy) === 'ASC' ? 'ASC' : 'DESC';
        $start = ($page - 1) * $limit;
        $Connection = QUI::getDataBaseConnection();
        $Platform = $Connection->getDatabasePlatform();
        $searchFields = ['subject', 'body', 'text', 'mailto', 'from', 'fromName'];

        $buildSearch = static function (\Doctrine\DBAL\Query\QueryBuilder $QueryBuilder) use (
            $search,
            $searchFields,
            $Platform
        ): void {
            if ($search === '') {
                return;
            }

            $orParts = [];

            foreach ($searchFields as $field) {
                $orParts[] = $Platform->quoteSingleIdentifier($field) . ' LIKE :search';
            }

            $QueryBuilder
                ->andWhere($QueryBuilder->expr()->or(...$orParts))
                ->setParameter('search', '%' . $search . '%');
        };

        $QueryBuilder = $Connection->createQueryBuilder()
            ->select(
                $Platform->quoteSingleIdentifier('id'),
                $Platform->quoteSingleIdentifier('subject'),
                $Platform->quoteSingleIdentifier('lastsend'),
                $Platform->quoteSingleIdentifier('retry'),
                $Platform->quoteSingleIdentifier('status'),
                $Platform->quoteSingleIdentifier('mailto')
            )
            ->from($Platform->quoteSingleIdentifier(Queue::table()))
            ->orderBy($Platform->quoteSingleIdentifier($sortOn), $sortBy)
            ->setFirstResult($start)
            ->setMaxResults($limit);

        $buildSearch($QueryBuilder);
        $result = $QueryBuilder->executeQuery()->fetchAllAssociative();

        $CountQueryBuilder = $Connection->createQueryBuilder()
            ->select('COUNT(' . $Platform->quoteSingleIdentifier('id') . ')')
            ->from($Platform->quoteSingleIdentifier(Queue::table()));

        $buildSearch($CountQueryBuilder);
        $total = (int)$CountQueryBuilder->executeQuery()->fetchOne();

        $statusMap = [
            Queue::STATUS_ADDED => QUI::getLocale()->get('quiqqer/core', 'mailqueue.status.added'),
            Queue::STATUS_SENT => QUI::getLocale()->get('quiqqer/core', 'mailqueue.status.sent'),
            Queue::STATUS_SENDING => QUI::getLocale()->get('quiqqer/core', 'mailqueue.status.sending'),
            Queue::STATUS_ERROR => QUI::getLocale()->get('quiqqer/core', 'mailqueue.status.error'),
            Queue::STATUS_CANCELED => QUI::getLocale()->get('quiqqer/core', 'mailqueue.status.canceled')
        ];

        foreach ($result as $key => $row) {
            $lastSend = (int)$row['lastsend'];
            $status = (int)$row['status'];

            $result[$key]['mail_to_display'] = $formatAddressList($row['mailto'] ?? '[]');
            $result[$key]['lastsend_display'] = $lastSend > 0
                ? QUI::getLocale()->formatDate($lastSend)
                : '-';
            $result[$key]['status_label'] = $statusMap[$status] ?? '-';

            if (!isset($result[$key]['subject']) || trim((string)$result[$key]['subject']) === '') {
                $result[$key]['subject'] = '-';
            }
        }

        return [
            'total' => $total,
            'page' => $page,
            'data' => $result
        ];
    },
    ['params'],
    'Permission::checkAdminUser'
);
