# Reusing the authorization-test project (#1553)

The 13 project/media authorization classes now extend `ProjectAuthorizationTestCase` and share the existing `ProjectTestHelper` project. They run sequentially in the main PHPUnit process. Their 58 test methods and assertions are preserved; users, sites and media objects are still created for each method. The real Composer download in `RunProcessorTest` remains part of the full suite.

The shared base restores AJAX registrations and shutdown callbacks, session and permission actors (including MCP), user/permission caches, project/media object caches and queued messages. Cleanup also destroys leftover media trash and removes ACLs for deleted objects. A snapshot of the highest existing site/media IDs keeps objects belonging to other tests out of this cleanup. Parallel tests against the same project/database are not supported.

Existing per-class cleanup remains in place. The shared cleanup runs in `finally`, including when setup fails. New regression tests exercise restoration after an exception, descendant trash/ACL cleanup while preserving existing objects, and translation cleanup.

`ProjectTestHelper` and the end-of-suite cleanup use the same `TestCleanup::cleanupProject()` implementation. It now:

- Keeps tables belonging to another configured project with a longer name: cleaning `phpunit` must not delete `phpunit_45_de_sites`.
- Removes media ACLs and the project's media-trash directory as well as site/project ACLs.
- Removes the `project/<name>` translation rows before removing the generated locale files, so later package setups cannot republish deleted fixtures.

The password-reset test now compares the identifier prompt with its current translation. It still asserts that the expected prompt is present, including on a German installation.

## Independent test processes

`TestCleanup::execute()` is also registered by other packages, including frontend-users. Previously, the end of one package's PHPUnit run could remove every configured `phpunit*` project, even if another process was still using it. Parallel frontend-users work during the original measurements exposed this overlap.

Projects now have a process-held filesystem lock before creation. `ProjectTestHelper` reserves its project name; the MCP project lifecycle test also reserves its original and renamed project names. A foreign cleanup skips locked projects. The owner releases its lock after cleanup, and the operating system releases it if the process terminates. Shutdown after a fork must not execute the parent's cleanup. Dedicated subprocess regression tests cover exclusion, owner termination and forked shutdown.

Empty lock files intentionally remain under `var/phpunit-project-locks/`: unlinking a lock file could let two processes lock different inodes for the same project. This directory must be shared by processes using the same installation. This protection prevents foreign cleanup from destroying an active project; it does not make arbitrary concurrent writes to installation configuration or caches safe.

## Validation

Use the normal suite commands; no tests or groups need to be excluded:

```sh
./tools/phpunit --do-not-cache-result --no-coverage
./tools/phpunit --do-not-cache-result --no-coverage --order-by=random --random-order-seed=1553
```

The initial unmodified run took 14:29.006 but reported 64 errors and two failures. Missing project tables caused cascading setup errors. The hard-coded English password prompt was another failure. That failed run, and later runs affected by concurrent cleanup, are not valid speedup baselines; no percentage improvement is claimed for the full suite.

The implementation preserves all 1,031 existing tests and adds eight regression tests. The 58 authorization test method bodies are unchanged. No Composer source or test was modified. Timings use PHP 8.5.10 and PHPUnit 10.5.63 without coverage or the result cache.

The full normal run completed in **3:10.082**, with 1,039 tests and 4,444 assertions. The final randomized run (seed 1553) completed in **3:01.627**, with 1,039 tests and 4,446 assertions. Both had zero errors/failures and the existing two skipped and 14 incomplete tests. The extra two assertions in the final run check reservation of the MCP lifecycle project's original and renamed names.

PHPStan, PHP syntax and coding-style checks passed. A separate check started an independent PHPUnit process with the actual frontend-users bootstrap while the core fixture remained active: frontend-users exited successfully and the core site/media tables survived its shutdown cleanup.

The final audit found no leftover test projects, project tables, fixture users, ACL rows, active project directories, translation groups, generated locale files or authorization-test input files. Persistent empty coordination lock files remain as described above.

## Local SQLite follow-up

The desired isolation is for the entire PHPUnit run, including integration tests. The existing order-package switch uses SQLite locally and the configured database when `GITLAB_CI=true`. Applying that to the full core suite requires a SQLite file shared by one run's PHP/HTTP child processes, plus separate installation configuration, project directories and caches. The current change does not implement that switch. Some existing core unit tests already use SQLite in memory.
