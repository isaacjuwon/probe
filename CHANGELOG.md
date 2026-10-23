# Release Notes

## [Unreleased](https://github.com/isaacjuwon/probe/compare/v0.1.0 - Initial Release...HEAD)

### Fixed

- Collapse endpoint folders by default on initial load and grouping changes.
- Fix explorer sidebar scroll containment by enforcing `min-h-0` on flex items and adding `overflow-hidden` to `ScrollArea`.
- Fix vertical scrollbar orientation variant matching in Ferrite Tailwind styles.

## [v0.1.0 - Initial Release](https://github.com/isaacjuwon/probe/compare/v0.1.0...v0.1.0 - Initial Release) - 2026-10-23

Initial release of **Probe** - Dev-only in-app REST client and API inspector for Laravel 12 & 13.

### Features

- **Zero-Config Endpoint Discovery**: Automatic detection of registered routes, URI parameters, and HTTP verbs without annotations or attributes.
- **REST & Laravel API Versioning**: Multi-strategy API version detection (URI paths, route prefixes, route names, controller namespaces, middleware, docblocks), domain-based resource grouping, and top-bar version switcher.
- **Route Auth Detection & Persona Switching**: Visual auth badges for Sanctum, API Key, Bearer, Basic, and Session auth; 1-click Sanctum user impersonation.
- **Validation Rule Reflection**: Automatic schema derivation from FormRequests, inline $request->validate(), and Spatie Laravel Data with realistic faker examples.
- **Real-Time Request Relay**: In-process dispatch through Laravel HTTP Kernel (eliminating rtisan serve worker deadlocks) and standard HTTP transport.
- **Dry Run Mode**: Safe testing of destructive mutations (POST, PUT, DELETE) with automatic database transaction rollback.
- **Telemetry & Cookie Profiler**: Live capture and inspection of SQL queries (with execution times and connection info), application logs, dumped variables, and decrypted Laravel cookies.
- **One-Click Pest Test Generator**: Turn any exploratory request/response into an idiomatic Pest PHP feature test.
- **Precompiled Ferrite Theme UI**: Responsive, zero-node-dependency Svelte 5 SPA with Ferrite dark and high-contrast light themes, CodeMirror 6 JSON editor, and keyboard shortcuts (Cmd+K command palette, Cmd+Enter dispatch).
- **Artisan Tooling**: probe:install, probe:export (OpenAPI/Postman/Scribe catalog export), and probe:scribe-sync.

## [v0.1.0](https://github.com/isaacjuwon/probe/releases/tag/v0.1.0) - 2026-10-23

Initial release of **Probe** — Dev-only in-app REST client and API inspector for Laravel 12 & 13.

### Features

- **Zero-Config Endpoint Discovery**: Automatic detection of registered routes, URI parameters, and HTTP verbs without annotations or attributes.
- **REST & Laravel API Versioning**: Multi-strategy API version detection (URI paths, route prefixes, route names, controller namespaces, middleware, docblocks), domain-based resource grouping, and top-bar version switcher.
- **Route Auth Detection & Persona Switching**: Visual auth badges for Sanctum, API Key, Bearer, Basic, and Session auth; 1-click Sanctum user impersonation.
- **Validation Rule Reflection**: Automatic schema derivation from FormRequests, inline `$request->validate()`, and Spatie Laravel Data with realistic faker examples.
- **Real-Time Request Relay**: In-process dispatch through Laravel HTTP Kernel (eliminating `artisan serve` single-threaded worker deadlocks) and standard HTTP transport.
- **Dry Run Mode**: Safe testing of destructive mutations (`POST`, `PUT`, `DELETE`) with automatic database transaction rollback.
- **Telemetry & Cookie Profiler**: Live capture and inspection of SQL queries (with execution times and connection info), application logs, dumped variables, and decrypted Laravel cookies.
- **One-Click Pest Test Generator**: Turn any exploratory request/response into an idiomatic Pest PHP feature test.
- **Precompiled Ferrite Theme UI**: Responsive, zero-node-dependency Svelte 5 SPA with Ferrite dark and high-contrast light themes, CodeMirror 6 JSON editor, and keyboard shortcuts (`Cmd+K` command palette, `Cmd+Enter` dispatch).
- **Artisan Tooling**: `probe:install`, `probe:export` (OpenAPI/Postman/Scribe catalog export), and `probe:scribe-sync`.
