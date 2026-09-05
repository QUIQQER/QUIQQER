# Persistent IP throttling

`QUI\Security\Throttle::acquireForIp($ip, $package, $action, $limit, $windowSeconds)`
consumes one request from a fixed database-backed window. It returns `true` when
reserved and `false` when exhausted. The window starts on the first request.
Successful requests and failures both count; they are not released.
Invalid arguments and database failures throw exceptions. Reserve before starting
the protected operation's transaction so a later rollback cannot undo the budget.

Use Symfony's trusted-proxy-aware `QUI::getRequest()->getClientIp()` for HTTP
requests, never an unchecked forwarding header. Equivalent IPv6 spellings and
IPv4-mapped IPv6 addresses share one budget. The database stores hashed source
keys, counts and expiry times. Packages and actions have separate budgets.
Existing `acquireForUser()` mail reservations and their release behavior are
unchanged.

## Login

The Core `ajax_users_login` endpoint reserves before processing authentication,
after rejecting cross-origin requests. Its shared source-IP budget covers frontend,
backend, all authenticators and both authentication steps. Every attempt counts,
including unknown names, malformed requests and successful logins. Neither session
rotation, cookie deletion, changing account/authenticator nor a successful login
resets the budget.

The default is 60 requests in 15 minutes. Administrators can change
`auth_settings.loginIpLimit` in the authentication settings. Valid values are
integers from 1 to 1000000; missing/invalid values use 60. Users behind a shared
office/mobile address share this limit. Expiry is fixed; blocked requests do not
extend it. Other IP addresses retain their own budget.

Exhaustion or a missing source address returns a translated AJAX exception with
code 429. Storage failures do not allow authentication to continue. Existing
per-account backoff, session failure handling, password checks and MFA stay active.
The frontend-users availability/resend limits are independent.

This covers the browser login endpoint; direct PHP/console authentication is not
subject to this HTTP source-IP policy.

## Setup and checks

Run Core package setup after deploying the schema and settings:

```sh
./console quiqqer:package --setup=quiqqer/core
```

This adds an `attempts` column to the existing `security_throttles` table and
imports the configuration and translations. Existing reservations are preserved.
The existing Core cleanup removes expired windows.

Regression tests cover atomic budget exhaustion, equivalent IPs, session changes,
unknown account input, independent user-mail reservations and simultaneous workers.
Concurrency tests default to an isolated SQLite database. For another database,
`QUIQQER_IP_THROTTLE_TEST_DATABASE` may contain DBAL connection parameters for a
dedicated disposable test database; the test imports the Core schema and clears
its throttle table.
