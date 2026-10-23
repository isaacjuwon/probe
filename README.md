<div align="center">
    <h1>Probe</h1>
    <p><strong>Dev-only, in-app REST client and API inspector for Laravel</strong></p>
</div>

<p align="center">
    <a href="https://packagist.org/packages/isaacjuwon/probe"><img src="https://img.shields.io/packagist/v/isaacjuwon/probe.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/isaacjuwon/probe"><img src="https://img.shields.io/packagist/php-v/isaacjuwon/probe.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/isaacjuwon/probe"><img src="https://badge.laravel.cloud/badge/isaacjuwon/probe?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/isaacjuwon/probe/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/isaacjuwon/probe/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/isaacjuwon/probe"><img src="https://img.shields.io/packagist/dt/isaacjuwon/probe.svg?style=flat-square" alt="Total Downloads"></a>
</p>

---

**Probe** gives your Laravel application a fast, self-hosted in-browser REST client (similar to Postman or Insomnia) embedded directly inside your development environment.

It automatically inspects your application's routes, reflects on controller actions and `FormRequest` classes, maps validation rules into request body schemas with realistic faker examples, and lets you execute API requests in real time while capturing SQL queries, logs, dumps, and execution durations.

📖 **Documentation**: [Comprehensive User Guide](docs/USER_GUIDE.md) &bull; 📰 **Article**: [Introducing Probe](docs/INTRODUCING_PROBE.md)

## Highlights

- 🚀 **Zero Configuration Endpoint Discovery**: Discovers all registered API routes, URI parameters, HTTP verbs, and controller bindings without manual annotations or attributes.
- 🌿 **Standard API Versioning & Global Switcher**: Auto-detects versions across URI path segments (`/api/v1/...`, `/api/2026-10-01/...`), route group prefixes, route names (`v1.orders.index`), controller namespaces, and docblocks. Features a global top-bar version switcher and smart domain resource grouping.
- 🔒 **Smart Route Auth Indicators & Resolution**: Real-time identification of Sanctum, API Key, Bearer, Basic, and Session auth with minimal visual badges. Built-in 1-click Sanctum user impersonation and inline API key resolution banners directly below the URL bar.
- 🧪 **One-Click Pest Test Generator**: Turn any exploratory API request and response into an idiomatic Pest PHP feature test ready to paste into your test suite with one click.
- 🛡️ **Zero Migrations & Dry Run Protection**: Requires 0 database tables. Test destructive mutations (`POST`, `PUT`, `DELETE`) safely with automatic database transaction rollbacks.
- 📋 **Deep Validation Reflection**: Extracts validation constraints from `FormRequest` classes, inline `$request->validate()` calls, and Spatie Laravel Data objects into typed parameter schemas with smart example generators.
- ⚡ **Real-Time Request Relay**: Executes requests directly via Laravel's HTTP Kernel in-process (preventing single-threaded worker deadlocks with `php artisan serve`) or HTTP client, tracking round-trip duration, response headers, and payloads.
- 🔍 **Integrated SQL Query & Log Inspector**: Automatically captures database queries executed during each request (with exact SQL, parameters, and timings), framework logs, and dumped variables.
- 🍪 **Decrypted Cookie Inspector**: Real-time inspection and decryption of Laravel encrypted cookies, session identifiers, and signed cookies.
- 🎨 **Ferrite Theme UI**: Modern dark-mode SPA built with Svelte 5 runes, Tailwind CSS v4, and CodeMirror 6 JSON editor. Precompiled into `public/probe.js` and `public/probe.css`—no Node or frontend tooling required in consuming apps.
- 💾 **IndexedDB & File Persistence**: Tabs and environment variables persist across browser reloads via IndexedDB. Saved request templates are stored locally in JSON format with automatic secret masking.
- ⌨️ **Keyboard Centric**: Built-in command palette (`Cmd+K` / `Ctrl+K`), quick dispatch (`Cmd+Enter` / `Ctrl+Enter`), tab switching, and `{{ variable }}` interpolation.

---

## Requirements

- **PHP**: `^8.3` or `^8.4`
- **Laravel**: `^12.0` or `^13.0`

---

## Installation

Install the package via Composer into your development dependencies:

```bash
composer require isaacjuwon/probe --dev
```

Probe automatically publishes its prebuilt assets upon install. You may also run the interactive installer at any time:

```bash
php artisan probe:install
```

### Manual Publishing (Optional)

To publish or update the configuration file:

```bash
php artisan vendor:publish --tag="probe-config"
```

To republish the prebuilt frontend assets:

```bash
php artisan vendor:publish --tag="probe-assets" --force
```

---

## Quick Start

1. Start your local Laravel development server:
   ```bash
   php artisan serve
   ```
2. Open your browser and navigate to:
   ```
   http://localhost:8000/probe
   ```
3. Your application endpoints will be automatically discovered in the left sidebar. Select any endpoint to inspect its parameters, auto-fill example request bodies, and send the request.

---

## Authorization & Security

By default, Probe is strictly restricted to `local` and `testing` environments. Access is authorized using the `viewProbe` gate.

To customize who can access Probe (for example, to allow access to specific developers or roles on a staging server), define the `viewProbe` gate in your `AppServiceProvider`:

```php
namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define(
            ability: 'viewProbe',
            callback: fn (?User $user = null): bool => app()->environment(['local', 'testing']) 
                || ($user !== null && $user->is_developer),
        );
    }
}
```

---

## Configuration Reference

The published `config/probe.php` file contains all customizable options:

```php
return [
    /*
     * Enable or disable Probe dashboard and API relay routes.
     */
    'enabled' => env('PROBE_ENABLED', true),

    /*
     * URL path where the dashboard will be mounted.
     * Default: '/probe'
     */
    'path' => 'probe',

    /*
     * Middleware applied to Probe routes.
     */
    'middleware' => ['web'],

    /*
     * Environments where Probe is authorized to boot.
     */
    'allowed_environments' => ['local', 'testing'],

    /*
     * Whitelist of permitted hostnames for relay requests.
     * Empty array allows the application URL and localhost.
     */
    'allowed_hosts' => [],

    /*
     * Transport mechanism for request execution: 'http' or 'in-process'.
     */
    'transport' => 'http',

    /*
     * Active endpoint discovery sources.
     * Available: 'router', 'scribe'
     */
    'sources' => ['router', 'scribe'],

    /*
     * Filter routes to include in the endpoint tree.
     */
    'route_filters' => [
        'prefixes' => ['api'],
        'middleware' => ['api'],
    ],

    /*
     * Scribe documentation synchronization settings.
     */
    'scribe' => [
        'enabled' => 'auto',
        'path' => null,
    ],

    /*
     * Storage driver for saved request templates.
     */
    'storage' => [
        'disk' => 'local',
        'path' => 'probe',
    ],
];
```

---

## User Interface & Features

### 1. Endpoint Tree & Smart Resource Grouping
The left sidebar presents all discovered endpoints organized by domain resource (e.g. `Orders`, `Users`, `Products`), controller, or version:
- **Standard API Versioning**: Automatically reflects version identifiers from URI segments (`/api/v1/...`, `/api/2026-10-01/...`), route prefixes, route names (`v1.orders.index`), controller namespaces, middleware, and docblocks.
- **Smart Resource Grouping**: Version segments are automatically stripped from group names so your endpoints group cleanly under their domain resource (`Orders`) instead of being dumped into monolithic `V1` folders, with clear `V1` or `V2` badges on each route row.
- **Quick Search & Scope Filter**: Filter between API routes, Web routes, or search by URI or HTTP method.

### 2. Global Top-Header Version Switcher
When your application contains multiple API versions:
- An interactive version dropdown appears in the global top header (`All Versions`, `v1`, `v2`, `unversioned`).
- Scopes the entire route tree to the selected version in one click.
- Preserves your selection across browser reloads via `localStorage`.

### 3. Smart Route Auth Indicators & 1-Click Resolution
Probe detects your route middleware and controller annotations to indicate required security:
- **Minimal Icon Badges**: Route rows display clean, color-coded icons (🔒 Sanctum, 🔑 API Key, 👤 Basic, 🛡️ Bearer, 🍪 Session). Public routes remain badge-free to eliminate clutter.
- **Auth Filter Pills**: Clickable pills at the top of the sidebar let you filter routes by authentication scheme with real-time route counts (`All`, `🔒`, `🔑`, `👤`, `🌐`).
- **1-Click Sanctum Persona Switcher**: When selecting a Sanctum-protected route, a user dropdown appears directly below the URL bar allowing you to impersonate any application user in 1 click.
- **Inline API Key Input**: When selecting an API Key-protected route, an inline header input (`Requires API Key: X-API-Key`) appears directly below the URL bar so you can paste your key immediately without leaving the request view.
- **Auth Mismatch Warnings**: If your active tab auth does not match the route's expected auth scheme, Probe alerts you and provides an instant resolution button.

### 4. Dry Run Mode (Automatic Database Rollback)
Next to the **Send** button, toggle the **Dry Run** switch:
- Dispatches the request inside a database transaction.
- When the response is received, all database mutations (`INSERT`, `UPDATE`, `DELETE`) are **automatically rolled back**.
- Safely test destructive API endpoints against local development databases without corrupting test data.

### 5. Request Builder & Magic Example Generation
- **Path & Query Parameters**: Dedicated editors for URI `{param}` placeholders and query strings.
- **Headers**: Custom header editor with autocomplete suggestions (`Accept: application/json`, etc.).
- **CodeMirror 6 JSON Editor**: Dark syntax-highlighted editor with bracket matching, formatting, and JSON validation.
- **Magic Example Generation**: Probe analyzes your `FormRequest` validation rules (e.g. `email`, `in:admin,user`, `min:18`) and pre-populates realistic sample payload values automatically.

### 6. Response Viewer & Real-Time Telemetry
When a request is executed (`Cmd+Enter` / `Ctrl+Enter`):
- **Status & Duration**: HTTP status badge and exact round-trip execution duration in milliseconds.
- **1-Click Pest Feature Test Generator**: Click **Copy as Pest Test** to generate an idiomatic Pest PHP feature test based on the actual request and response, ready to paste directly into your test suite.
- **Database Telemetry**: Dedicated tab listing every executed SQL query with parameter bindings, execution time, and slow-query warnings.
- **Decrypted Cookie Inspector**: Real-time inspection of encrypted cookies, session identifiers, and signed cookies with in-app decryption and JSON parsing.
- **Validation Errors (422 Diffing)**: Inspect exact failing `FormRequest` rules and fields side-by-side.
- **Stream Events (SSE)**: Built-in event stream viewer for Server-Sent Events with timestamped event cards.

### 7. Template Variables (`{{ variable }}`)
Any URL, header value, query parameter, or JSON body string can reference variables using double curly braces:
```
http://localhost:8000/api/users/{{ userId }}
Authorization: Bearer {{ apiToken }}
```
Variables can be managed dynamically from the environment settings panel or command palette with live preview interpolation.

### 8. Command Palette & Keyboard Shortcuts
Press `Cmd+K` or `Ctrl+K` to open the command palette:
- Filter and jump directly to any endpoint.
- Trigger actions: Send request (`Cmd+Enter`), open new tab (`Cmd+T`), duplicate tab, close tab (`Cmd+W`), or toggle theme.

---

## Artisan Commands

### `probe:install`
Installs Probe into your application, publishes configuration, and ensures public assets are linked.
```bash
php artisan probe:install
```

### `probe:export`
Exports all discovered endpoints in JSON format to standard output or a file.
```bash
php artisan probe:export > api-catalog.json
```

### `probe:scribe-sync`
Synchronizes rich documentation, descriptions, and examples from [Knuckles Scribe](https://scribe.knuckles.mfcode.com/) if installed.
```bash
php artisan probe:scribe-sync
```

---

## Testing in Applications

When writing tests in applications that use Probe:

```php
use Illuminate\Support\Facades\Http;

it('tests probe dashboard accessibility', function () {
    $response = $this->get('/probe');

    $response->assertOk()
        ->assertSee('window.__PROBE__ =', false);
});

it('tests api relay with fake responses', function () {
    Http::fake([
        'http://localhost/api/users*' => Http::response(
            body: ['data' => [['id' => 1, 'name' => 'Alice']]],
            status: 200,
        ),
    ]);

    $response = $this->postJson('/probe/relay', [
        'method' => 'GET',
        'url' => 'http://localhost/api/users',
        'headers' => ['Accept' => 'application/json'],
        'body' => '',
        'auth' => ['type' => 'none'],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.status', 200);
});
```

---

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on recent releases.

## Contributing

Contributions, bug reports, and feature requests are welcome! Please review our [contributing guidelines](.github/CONTRIBUTING.md).

## Security

Probe is designed specifically for development environments. If you discover a security vulnerability within Probe, please review our [Security Policy](.github/SECURITY.md).

## Credits

- [Isaac Juwon](https://github.com/isaacjuwon)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
