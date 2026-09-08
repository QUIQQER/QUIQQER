<?php

/**
 * Check session continuity before sending credentials, without exposing the session ID.
 */

use QUI\Security\RequestOrigin;

QUI::getAjax()->registerFunction(
    'ajax_users_checkSession',
    static function (mixed $token = 'start'): string | bool {
        RequestOrigin::assertNotCrossOrigin();

        $Session = QUI::getSession();
        $key = 'quiqqer.security.loginSessionCheck';
        $stored = $Session->get($key);

        if ($token !== 'start') {
            return is_string($token)
                && is_string($stored)
                && $stored !== ''
                && hash_equals($stored, $token);
        }

        // Reuse the diagnostic value so parallel login controls cannot invalidate each other's check.
        if (!is_string($stored) || $stored === '') {
            $stored = bin2hex(random_bytes(32));
            $Session->set($key, $stored);
        }

        return $stored;
    },
    ['token']
);
