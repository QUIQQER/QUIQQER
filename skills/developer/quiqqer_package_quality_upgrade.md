---
name: quiqqer_package_quality_upgrade
description: Use when modernizing and completing a QUIQQER package with current PHIVE tools, PHPStan 2 at level 8, DBAL and PostgreSQL-compatible database access, portable database.xml schemas, CI stubs for optional dependencies, PHPUnit integration tests, package metadata, licensing, README documentation, and required visual assets.
category: developer
---

# QUIQQER Package Quality Upgrade

Modernize one package at a time. Preserve behavior and keep commits reviewable. Work in the repository and branch the
developer has already selected. Do not switch branches or infer that another branch would be more appropriate.
Load `quiqqer_developer_workflow` as well when commit or handover rules are needed.

## 1. Respect The Current Repository State

Inspect the worktree before changing files:

```shell
git status --short
git diff
```

Preserve unrelated local changes. Never switch branches, initialize a different package checkout, discard changes, or
rewrite history as part of this quality workflow unless the developer explicitly requests it.

## 2. Inventory The Package

Inspect at least:

- `composer.json` and `composer.lock` context
- `phive.xml`
- `phpstan.neon`, `phpstan.dist.neon`, and baselines
- `phpunit.dist.xml` and `tests/`
- `phpcs.xml.dist`
- CI configuration
- `database.xml`
- active legacy database calls

Use `rg` to find suppressed analysis, optional dependencies, PDO, MySQL-only SQL, and old database APIs. Include dead or
commented legacy implementations in the cleanup decision.

## 3. Update The Local Toolchain

Use package-local PHIVE tools. Upgrade the required quality tools with:

```shell
phive install phpstan:2.* phpcs:4.* phpcbf:4.*
```

Keep existing PHPUnit and other tools unless their upgrade is part of the task. Verify that `phive.xml`, Composer scripts,
and CI invoke the same package-local tools. Prefer these commands:

```shell
./tools/phpcs
./tools/phpcbf
./tools/phpstan
./tools/phpunit
```

Keep the isolated toolchain/CI change separate from large analysis fixes.

## 4. Reach PHPStan Level 8

Run PHPStan without trusting an old result cache:

```shell
./tools/phpstan clear-result-cache
./tools/phpstan --no-progress --error-format=table
```

Work in controlled stages:

1. Make PHPStan 2 run with the existing configuration.
2. Configure the supported PHP version range.
3. Remove obsolete exclusions and ignores.
4. Empty the baseline; do not regenerate it to hide findings.
5. Set `level: 8` if the package is below level 8.
6. Fix findings in related groups and commit each coherent group.

Ensure the PHPStan configuration contains this version range below `parameters`:

```neon
parameters:
    phpVersion:
        min: 80200
        max: 80509
```

Keep existing `parameters` entries and merge `phpVersion` into that block; do not create a second `parameters` section.

Fix root causes: real parameter and return types, nullable paths, failed conversions, array shapes, and control flow. Do not
weaken production types or add blanket ignores. Replace legacy static access such as `QUI::$Ajax` with supported accessors
such as `QUI::getAjax()`.

Prefer PHP's nullsafe operator (`?->`) when `null` may naturally propagate and the surrounding condition, fallback, or
return value already handles it correctly. Use an explicit null check only when `null` requires distinct control flow,
logging, state changes, or an exception. Do not add a verbose guard when a nullsafe call expresses the same behavior.

### Optional Dependency Stubs

CI installations may intentionally omit optional ERP, payment, PDF, or integration packages. If PHPStan must understand
their types, add minimal analysis-only shims under `tests/phpstan-shims/` or the package's established stub directory.

- Declare only the classes, interfaces, constants, and method signatures the package consumes.
- Guard declarations with `class_exists()` or `interface_exists()` when runtime overlap is possible.
- Load them through `scanFiles` or the established PHPStan bootstrap.
- Never place compatibility shims in production source code.
- Do not use a shim when the dependency is actually required and missing from `composer.json`.

Run PHPStan both in the full local installation and in CI. CI is authoritative for optional-dependency coverage.

## 5. Migrate Database Access To DBAL

Follow the Core DBAL migration rules from
`https://dev.quiqqer.com/quiqqer/core/-/work_items/1525` and the database XML reference at
`https://quiqqer.com/docs/developer/package-reference/database-xml`.

Remove active uses of:

- `QUI::getDataBase()` / `QUI::getDatabase()`
- legacy `fetch`, `insert`, `update`, `delete`, and `execSQL` database wrappers
- direct `QUI::getPDO()` queries
- MySQL backticks and MySQL-only DDL or functions
- manually concatenated values and `LIMIT offset,count`

Use `QUI::getDataBaseConnection()` for simple DBAL operations:

```php
$Connection = QUI::getDataBaseConnection();
$Connection->insert($table, $data);
$Connection->update($table, $data, $criteria);
$Connection->delete($table, $criteria);
```

Use `QUI::getQueryBuilder()` for filtering, joins, expressions, sorting, counts, and pagination:

```php
$QueryBuilder = QUI::getQueryBuilder();
$result = $QueryBuilder
    ->select('id')
    ->from(QUI\Utils\Doctrine::quoteIdentifier($table))
    ->where($QueryBuilder->expr()->eq('status', ':status'))
    ->setParameter('status', $status)
    ->setFirstResult($offset)
    ->setMaxResults($limit)
    ->executeQuery()
    ->fetchFirstColumn();
```

Parameterize every value. Whitelist dynamic sort columns and directions. Quote identifiers through the current database
platform or `QUI\Utils\Doctrine`; never apply identifier quoting to user-provided values. Preserve empty-result, count,
exception, sorting, and pagination behavior during conversion.

### Portable `database.xml`

Replace SQL fragments in `type` attributes with portable schema metadata:

```xml
<field type="bigint" autoincrement="true" primary="true">id</field>
<field type="string" length="255">title</field>
<field type="datetime" nullable="true" default="null">editDate</field>
<field type="boolean" default="0">active</field>
```

Use portable types, explicit nullability, explicit defaults, primary keys, and indexes for real lookup paths. Use composite
indexes only when the query patterns justify them. Validate XML and scan again for `AUTO_INCREMENT`, `UNSIGNED`, backticks,
engines, and other MySQL-only syntax.

## 6. Add PHPUnit Coverage

Add unit tests for isolated behavior and integration tests for database-backed workflows. Follow the package layout; a
typical integration setup uses:

```text
phpunit.dist.xml
tests/phpunit-bootstrap.php
tests/integration/
tests/stubs/
```

Integration tests must exercise public package APIs and the real QUIQQER bootstrap/database. Cover the workflow changed by
the migration, including relevant create, update, lookup, search, sort, pagination, archive, and delete behavior.

Make fixtures repeatable:

- use a package-specific unique prefix
- clean stale fixtures before the class and before each test
- clean in `tearDown()` and `tearDownAfterClass()`
- remove orphaned dependent rows as well as parent rows
- restore session users, configuration, events, and other global state
- skip only when required infrastructure is genuinely unavailable

Do not disable database tests merely because they run in CI when CI provides the intended integration environment. Add
narrow test stubs for optional classes when necessary; do not change production behavior to accommodate tests.

Run the integration suite twice consecutively. The second run detects incomplete cleanup, fixed IDs, leaked global state,
and ordering assumptions.

## 7. Complete The Package

Completion is mandatory for every full package quality upgrade. Inspect and improve the repository-local package metadata,
licensing, documentation, and required visual assets instead of merely reporting omissions.

### Composer Metadata

Follow `https://quiqqer.com/docs/developer/package-development#composer-metadata`.

- Use `quiqqer-module` for normal extension packages, `quiqqer-template` for project presentation packages, and
  `quiqqer-asset` only for the corresponding generated browser asset packages.
- Remove a `version` field. The QUIQQER update server derives and manages package versions.
- Replace personal author entries with the company maintainer entry:

```json
"authors": [
  {
    "name": "PCSG - Computer & Internet Service OHG",
    "email": "info@quiqqer.com",
    "homepage": "https://www.quiqqer.com",
    "role": "Maintainer"
  }
]
```

- Verify package name, description, homepage, support email, source URL, issue URL, PHP constraint, Core constraint, required
  PHP extensions, and package dependencies.
- Run `composer validate`. Keep Composer, `package.xml`, locales, and README metadata consistent.

### License And README

- Preserve the package's intended licensing meaning and use a valid SPDX identifier where one exists.
- Add or update the repository's `LICENSE` file and keep it consistent with Composer and `package.xml`.
- If the intended license cannot be determined unambiguously from existing repository evidence, ask the developer instead
  of inventing or changing a license.
- Create or improve the README without waiting for a separate request. Write it at least in English and include a clear
  title, description, installation, configuration when applicable, usage, relevant technical notes, license, and support.
- Remove obsolete personal developer attribution and stale instructions. Keep useful package-specific documentation.

### `package.xml`, Locales, And Images

- Verify localized title and short description, package image reference, support information, copyright, license, and all
  referenced locale variables. Ensure English locale text exists.
- Reuse suitable existing images. Never regenerate or replace an existing suitable image merely to standardize its file
  type, style, name, or location.
- Required visual asset types are the README header, package logo/icon, and GitLab project avatar image. Screenshots are not
  currently required.
- Generate only missing asset types with `https://completion.quiqqer.com/`. Supply the package title, the appropriate type
  (`QUIQQER`, `ERP`, `ecoyn`, or `Kimai`), and a Font Awesome icon. If the icon or type is ambiguous, ask the developer.
- The generator outputs `Readme.png` at 1200x600, `Logo.png` at 400x300, and `Gitlab.png` at 100x100. Preserve an
  established package image directory; otherwise place new assets under `bin/images/` and update repository references.
- Codex cannot assume permission to change the external GitLab project avatar. Report the intended `Gitlab` image's exact
  repository path so the developer can upload it manually.

Completion does not include screenshots, CI status review, milestone creation, version creation, tags, releases, or a list
of manual release steps.

Keep completion changes reviewable. Prefer separate Conventional Commits for metadata/license/README changes and visual
assets when both categories are changed.

## 8. Validate And Deliver

Run the complete package checks:

```shell
xmllint --noout database.xml
./tools/phpcs
./tools/phpstan clear-result-cache
./tools/phpstan --no-progress --error-format=table
./tools/phpunit
./tools/phpunit
git diff --check
```

Omit `xmllint` only when the package has no `database.xml`. Scan one final time for legacy database access and MySQL-only
constructs. Review `git diff` and confirm that the worktree contains only intended changes.

Prefer separate Conventional Commits for:

- toolchain and CI
- PHPStan/type fixes grouped by concern
- DBAL query migration
- portable database schema
- tests and fixtures

Commit or push only when the developer requests it. Report commits when present, PHPStan level, test count/assertions,
skipped tests, CI-only stubs, DBAL/schema changes, and any remaining risks.
