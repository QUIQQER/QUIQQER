<?php

/**
 * Delete one or many mail queue entries
 */

use QUI\Mail\Queue;
use QUI\Utils\System\File;

QUI::getAjax()->registerFunction(
    'ajax_system_mailqueue_delete',
    static function ($id): int {
        $ids = [];

        if (is_array($id)) {
            $ids = $id;
        } elseif (is_string($id)) {
            $id = trim($id);

            if ($id === '') {
                return 0;
            }

            $decoded = json_decode($id, true);

            if (is_array($decoded)) {
                $ids = $decoded;
            } elseif (str_contains($id, ',')) {
                $ids = explode(',', $id);
            } else {
                $ids = [$id];
            }
        } else {
            $ids = [$id];
        }

        $deleted = 0;

        foreach ($ids as $mailId) {
            $mailId = (int)$mailId;

            if ($mailId < 1) {
                continue;
            }

            QUI::getDataBase()->delete(
                Queue::table(),
                ['id' => $mailId]
            );

            $attachmentDir = Queue::getAttachmentDir($mailId);

            if (is_dir($attachmentDir)) {
                File::deleteDir($attachmentDir);
            }

            $deleted++;
        }

        return $deleted;
    },
    ['id'],
    'Permission::checkAdminUser'
);
