<?php

/**
 * Return one mail queue entry
 */

use QUI\Mail\Queue;

QUI::getAjax()->registerFunction(
    'ajax_system_mailqueue_get',
    static function ($id): array {
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

        $id = (int)$id;

        if ($id < 1) {
            return [];
        }

        $result = QUI::getDataBase()->fetch([
            'from' => Queue::table(),
            'where' => [
                'id' => $id
            ],
            'limit' => 1
        ]);

        if (!isset($result[0])) {
            return [];
        }

        $entry = $result[0];
        $lastSend = (int)$entry['lastsend'];

        $statusMap = [
            Queue::STATUS_ADDED => QUI::getLocale()->get('quiqqer/core', 'mailqueue.status.added'),
            Queue::STATUS_SENT => QUI::getLocale()->get('quiqqer/core', 'mailqueue.status.sent'),
            Queue::STATUS_SENDING => QUI::getLocale()->get('quiqqer/core', 'mailqueue.status.sending'),
            Queue::STATUS_ERROR => QUI::getLocale()->get('quiqqer/core', 'mailqueue.status.error'),
            Queue::STATUS_CANCELED => QUI::getLocale()->get('quiqqer/core', 'mailqueue.status.canceled')
        ];

        $entry['status_label'] = $statusMap[(int)$entry['status']] ?? '-';
        $entry['lastsend_display'] = $lastSend > 0
            ? QUI::getLocale()->formatDate($lastSend)
            : '-';

        $entry['mail_to_display'] = $formatAddressList($entry['mailto'] ?? '[]');
        $entry['reply_to_display'] = $formatAddressList($entry['replyto'] ?? '[]');
        $entry['mail_cc_display'] = $formatAddressList($entry['cc'] ?? '[]');
        $entry['mail_bcc_display'] = $formatAddressList($entry['bcc'] ?? '[]');

        if (!isset($entry['subject']) || trim((string)$entry['subject']) === '') {
            $entry['subject'] = '-';
        }

        $from = trim((string)($entry['from'] ?? ''));
        $fromName = trim((string)($entry['fromName'] ?? ''));

        if ($from !== '' && $fromName !== '') {
            $entry['from_display'] = $fromName . ' <' . $from . '>';
        } elseif ($from !== '') {
            $entry['from_display'] = $from;
        } elseif ($fromName !== '') {
            $entry['from_display'] = $fromName;
        } else {
            $entry['from_display'] = '-';
        }

        if (!isset($entry['errors']) || trim((string)$entry['errors']) === '') {
            $entry['errors'] = '-';
        }

        return $entry;
    },
    ['id'],
    'Permission::checkAdminUser'
);
