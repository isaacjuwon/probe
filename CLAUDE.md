# Probe

This repository is a Laravel package (`isaacjuwon/probe`). Keep the package focused, idiomatic, and easy for Laravel developers to install, test, and maintain.

## Package Conventions

- Use Laravel-native package APIs and the existing service provider shape before adding abstractions.
- Keep package names, namespaces, Composer metadata, publish tags, documentation, and examples aligned with `isaacjuwon/probe` and PHP namespace `Isaacjuwon\Probe`.
- Add only the files and dependencies needed for the package behavior being implemented.
- Prefer explicit Laravel package code over helper abstractions unless the extension point is real.
- Keep tests focused on observable package behavior through public APIs, service provider wiring, commands, routes, published resources, and documentation promises.

## Engineering Rules (Mandatory)

1. **Imported namespaces only**: Never write a fully qualified class name inline. Import with `use`. Alias only to resolve a real collision. Enforced via Pint `fully_qualified_strict_types` and `global_namespace_import`.
2. **Named arguments**: Use named arguments for every call with two or more arguments, any boolean or null literal, and every constructor of a value object or DTO. Single obvious arguments (`count($items)`) are exempt. Never mix positional and named arguments.
3. **Laravel native first**: Check framework features and facades (`Http`, `Gate`, `Cache`, `Storage`, `Crypt`, `Pipeline`, `Collection`, `Manager`) before writing custom code.
4. **Design patterns applied deliberately**: Patterns are used where they remove a real problem and named in the class or docblock so intent is obvious.
5. **SOLID with Single Responsibility**:
   - `declare(strict_types=1);` in every file.
   - `final` by default. Open for extension through contracts, not inheritance.
   - Value objects are `readonly`. Enums over string constants.
   - Actions are single-method invokable classes (`__invoke`).
   - Depend on contracts, bind in the container, inject through constructors. No `new` of services, no facade calls inside domain logic.
   - Max ~20 lines per method, ~150 per class. Past that, split.

## Quick Commands

- Full validation: `composer test`
- Formatting check: `composer lint:check`
- Format code: `composer lint`
- Static analysis: `composer analyse`
- Pest tests: `composer test:unit`
- Type coverage: `composer test:types`
- Workbench build: `composer build`
- Workbench server: `composer serve`

## Local Skills

- `package-scaffold`: use when adding package capabilities or wiring them through the service provider, including commands, migrations, routes, config, views, translations, assets, middleware, publish tags, workbench files, and console-only behavior.
- `package-testing`: use when adding or changing package tests with Pest 4/5 and Orchestra Testbench.
- `package-release`: use when preparing changelog, release notes, tags, or GitHub release workflow changes.
- `package-compatibility`: use when reviewing code, dependencies, or CI against the PHP and Laravel support matrix.
- `package-generate-skill`: use when updating the bundled Boost skill from the package implementation, README, and examples.
