---
name: quiqqer_developer_workflow
description: Use when developing QUIQQER packages or Core changes and you need the standard workflow for commits, checks, coding standards, branch handling, and merge-request preparation.
category: developer
---

# QUIQQER Developer Workflow

Use this skill for QUIQQER development work before changing code, preparing commits, or handing work over for review.

## Development Scope

- Prefer creating or extending a package for normal project work.
- Change Core only when the task explicitly targets platform behavior.
- Read the local repository structure before editing. Prefer existing package patterns over new abstractions.
- Keep changes small and reviewable. Do not mix unrelated formatting, refactoring, features, and fixes.

## Atomic Commits

Make every commit one complete reversible work unit:

- one feature
- one bug fix
- one isolated refactor
- one style-only change
- one dependency/tooling change
- tests for one behavior

If a file contains unrelated edits, split them before committing. Do not include broad code-style cleanup in a feature or fix commit unless it is limited to the changed lines.

## Commit Message Standard

Use Conventional Commits:

```text
<type>[(<scope>)][!]: <short summary>
```

Header rules:

- Write the header in English.
- Keep the header at most 72 characters.
- Start the summary with a lowercase letter.
- Use imperative, present tense.
- Do not end the header with a period.

Allowed types:

- `build`: build process, dependencies, package metadata, tooling inputs
- `ci`: CI configuration and scripts
- `docs`: documentation
- `feat`: new user-facing or package-facing capability
- `fix`: bug fix or correction of unwanted behavior
- `perf`: performance improvement
- `refactor`: code change that is neither a fix nor a feature
- `revert`: revert another commit
- `style`: code style-only change
- `test`: tests and fixtures

Use a scope when it clarifies the affected area:

```text
fix(locale): handle missing translation fallback
```

Mark breaking changes with `!` and a `BREAKING CHANGE` footer:

```text
build!: bump minimum PHP version to 8.1

BREAKING CHANGE: Drop support for PHP 7.4
```

Use footers for ticket references:

```text
Related: quiqqer/package#123
Closes: quiqqer/package#124
Acked-by: Jane Doe <jane@example.com>
```

Use `Related` when a commit is connected to an issue. Use `Closes` only when the issue should close after the commit reaches the release branch.

## Checks

Use the package-local tooling as the source of truth. Do not assume every package has the same scripts.

Common commands:

```shell
./tools/phpcs
./tools/phpstan
./tools/phpunit
composer test
```

If tools are missing, report that clearly instead of inventing a replacement. Treat PHPStan findings as code-quality issues and fix the type, API, or control-flow problem unless the package has a documented ignore.

## Coding Standards

- Use PSR-12 for PHP.
- Use package-local PHPCS and PHPStan when available.
- Use JSHint when a package provides JavaScript linting.
- Keep imports organized and names consistent with the package namespace.
- Add comments only when they clarify non-obvious behavior.

## Git Workflow

- Use `git pull --rebase` when updating a feature branch.
- Use `git revert <commit>` for pushed commits that must be undone.
- Avoid rewriting shared history unless the team explicitly agrees.
- Use `git fetch --prune` to clean stale remote branch references.

## Merge Request Handover

Before opening or handing over a merge request:

- Keep commits atomic.
- Verify commit messages.
- Run available PHPCS, PHPStan, and test commands.
- Document behavior changes and known test gaps.
- Target the repository's development flow; normal package work is reviewed into `next`.
- Do not publish versions directly. Maintainers handle release branches and version creation.

## References

- Developer docs: https://www.quiqqer.com/docs/developer/
- Workflow: https://www.quiqqer.com/docs/developer/development-workflow
- Coding standards: https://www.quiqqer.com/docs/developer/coding-standards
