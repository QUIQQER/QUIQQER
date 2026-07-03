---
name: quiqqer_extension_points
description: Use when adding or changing QUIQQER package extension points such as package.xml providers, XML configuration files, events, console tools, permissions, settings, controls, assets, or setup-related integration.
category: developer
---

# QUIQQER Extension Points

Use this skill when implementing QUIQQER package integrations or Core extension points. Add only the extension files and providers required by the task.

## Package First

- For normal feature work, create or extend a package instead of changing Core.
- Use `quiqqer-module` for normal extension packages.
- Use `quiqqer-template` only for template packages.
- Use `quiqqer/core` as the current platform dependency.
- Keep PHP classes autoloadable through Composer PSR-4 under `src/`.
- Put browser-accessible assets under `bin/`.

Minimal package shape:

```text
composer.json
package.xml
locale.xml
src/
bin/
```

Add further XML files only when the package actually provides that behavior.

## Composer Metadata

Every package needs valid Composer metadata:

```json
{
  "name": "vendor/package-name",
  "type": "quiqqer-module",
  "description": "Short package description.",
  "license": "GPL-3.0-or-later",
  "require": {
    "php": "^8.1",
    "quiqqer/core": "^2"
  },
  "autoload": {
    "psr-4": {
      "Vendor\\Package\\": "src/Vendor/Package"
    }
  }
}
```

Declare direct runtime dependencies in `require`. Use `suggest` for optional integrations.

## package.xml Providers

Use `package.xml` for QUIQQER-facing metadata and providers:

```xml
<quiqqer>
    <package>
        <title>
            <locale group="vendor/package" var="package.title"/>
        </title>

        <description>
            <locale group="vendor/package" var="package.description"/>
        </description>

        <provider>
            <desktopSearch src="\Vendor\Package\Search\Provider"/>
        </provider>
    </package>
</quiqqer>
```

Provider rules:

- Declare a provider only when the package implements that provider API.
- Use fully qualified, Composer-autoloadable class names.
- Keep package metadata consistent with `composer.json`.
- Use locale references for package title and description.
- Run setup after changing provider declarations.

Common provider types include `auth`, `desktopSearch`, `installationWizard`, `rest`, `mcp`, and `mcpSkill`.

## XML Files By Use Case

Use the smallest XML surface needed:

- `console.xml`: register console tools
- `database.xml`: create or update package database tables
- `events.xml`: register event listeners
- `locale.xml`: provide PHP and JavaScript translations
- `media.xml`: add media attributes
- `menu.xml`: add backend menu entries
- `permissions.xml`: declare permissions
- `settings.xml`: add package or project settings
- `site.xml`: add site types or site editor behavior
- `user.xml`: extend user data or UI
- `group.xml`: extend group data or UI
- `panel.xml` / `panels.xml`: add backend panels
- `widgets.xml`: declare desktop widgets

Setup imports package XML files. Run `./console setup` after adding or changing XML metadata in a development installation.

## Events

Register event listeners in `events.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<events>
    <event on="onPackageSetup" fire="\Vendor\Package\EventHandler::onPackageSetup"/>
</events>
```

Listener rules:

- Use the `on...` event name form in XML.
- Match the listener method signature to the event parameters.
- Guard setup listeners against unrelated packages:

```php
if ($Package->getName() !== 'vendor/package') {
    return;
}
```

- Use `priority` only when listener order matters.
- Keep request-level listeners small and predictable.
- Catch expected exceptions only when the event should not abort the workflow.

## Console Tools

Register runtime commands in `console.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<console>
    <tool exec="\Vendor\Package\Console\RebuildIndex"/>
</console>
```

Implement tools by extending `QUI\System\Console\Tool`. Use explicit package-scoped command names such as `vendor-package:rebuild-index`. Use `addArgument()` for documented arguments and avoid reserved argument names: `help`, `tool`, `listtools`, `u`, `p`, `username`, `password`.

Use `composer.json` scripts for repository checks. Use `console.xml` only for commands that run inside an installed QUIQQER system.

## Permissions

Declare package permissions in `permissions.xml` and check them where behavior is exposed to users, tools, Ajax endpoints, or MCP tools.

```xml
<permissions>
    <permission name="vendor.package.action" type="bool">
        <defaultvalue>0</defaultvalue>
    </permission>
</permissions>
```

Use dedicated permissions for sensitive write, delete, publish, update, cache, or automation actions.

## Controls And Assets

Use PHP controls when the server prepares attributes, templates, permissions, locale values, or package data. PHP controls extend `QUI\Control` and can set a JavaScript control through `setJavaScriptControl()`.

JavaScript controls are AMD modules loaded through RequireJS paths. The control name must match the value used in `data-qui` or `setJavaScriptControl()`.

Prefer established Core controls for common backend inputs:

- `controls/projects/project/site/Input`
- `controls/projects/project/media/Input`
- `controls/lang/InputMultiLang`
- `controls/lang/ContentMultiLang`
- `controls/editors/Input`
- `controls/users/Select`
- `controls/usersAndGroups/Select`

## Validation

After changing extension declarations:

- Validate edited XML syntax.
- Validate `composer.json`.
- Run `./console setup` in a development installation when package metadata must be re-imported.
- Run the package-local checks: `./tools/phpcs`, `./tools/phpstan`, and `./tools/phpunit`.
- Manually verify the feature in the administration interface, CLI, or relevant package workflow.

## Tests For Extension Work

Add or update PHPUnit tests for every fix and every feature that changes extension behavior.

Use the repository's existing test structure. Core is a useful reference:

- `phpunit.dist.xml` defines Unit and Integration suites.
- `tests/phpunit-bootstrap.php` loads the QUIQQER bootstrap and shared test helpers.
- `tests/unit/` contains isolated behavior tests.
- `tests/integration/` contains database, project, media, user, group, and runtime integration tests.
- `tests/stubs/` contains narrow compatibility stubs for optional or environment-specific classes.

For provider, XML, event, console, permission, and setup behavior:

- Test pure parsing or registration logic with unit tests when possible.
- Test database, project, media, or setup side effects with integration tests.
- Use fixtures for fake actions, fake providers, or controlled runtime collaborators.
- Use stubs when PHPUnit cannot load optional package dependencies or classes that only exist with a newer optional package version.
- Keep stubs minimal and local to the test suite. Do not weaken production code to make tests pass.

If a test cannot run because the environment is missing a real external service, mark only that integration path as skipped and keep unit coverage for the decision logic.

## References

- Package development: https://www.quiqqer.com/docs/developer/package-development
- XML configuration: https://www.quiqqer.com/docs/developer/package-reference/xml-files
- package.xml: https://www.quiqqer.com/docs/developer/package-reference/package-xml
- Events: https://www.quiqqer.com/docs/developer/events
- CLI: https://www.quiqqer.com/docs/developer/cli
