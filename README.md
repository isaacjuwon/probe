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

## Highlights

- 🚀 **Zero Configuration Endpoint Discovery**: Discovers all registered API routes, URI parameters, HTTP verbs, and controller bindings without manual annotations.
- 📋 **Deep Validation Reflection**: Extracts validation constraints from `FormRequest` classes, inline `$request->validate()` calls, and Spatie Laravel Data objects into typed parameter schemas.
- ⚡ **Real-Time Request Relay**: Executes requests against your local application or allowed external services, tracking round-trip duration, response headers, status codes, and JSON payloads.
- 🔍 **Integrated Query & Log Inspector**: Automatically captures database queries executed during each request (with exact SQL, parameters, and timings), framework logs, and dumped variables.
- 🎨 **Ferrite Theme UI**: Modern dark-mode SPA built with Svelte 5 runes, Tailwind CSS v4, and CodeMirror 6 JSON editor. Fully precompiled into `public/probe.js` and `public/probe.css`—no Node or frontend tooling required in consuming apps.
- 💾 **IndexedDB & File Persistence**: Tabs and environment variables persist across browser reloads via IndexedDB. Saved request templates are stored locally in JSON format with automatic secret masking.
- 🛡️ **Zero Database Migrations & Safe by Default**: Requires 0 database tables, restricts execution to authorized local/testing environments via Laravel Gate, and guards against unauthorized outbound hosts.
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

### 1. Endpoint Tree & Search
The left sidebar presents all discovered endpoints grouped by controller resource (e.g., `Users`, `Posts`, `Orders`).
- Quick search filters endpoints by URI or HTTP method.
- Click any endpoint to open it into the active tab or launch a new tab.

### 2. Tab Management & Persistence
- Work with multiple concurrent API requests using independent tabs.
- Active tabs and unsent drafts are continuously synchronized to browser IndexedDB, persisting across page refreshes.
- Use `Cmd+T` / `Ctrl+T` to open a new tab, and `Cmd+W` / `Ctrl+W` to close the active tab.

### 3. Request Builder
- **Parameters & Query Strings**: Key-value editor for URI path parameters and query strings.
- **Headers**: Add custom headers, with pre-configured suggestions (`Accept: application/json`, etc.).
- **Authentication**:
  - `None`: Unauthenticated requests.
  - `Bearer`: Automatically injects `Authorization: Bearer <token>`.
  - `Basic`: Base64 encodes username and password.
  - `API Key`: Configurable header key (e.g., `X-API-KEY`) and token value.
- **Body Editor**: Full-featured CodeMirror 6 JSON editor with syntax error detection, bracket matching, and format-on-click.

### 4. Response & Telemetry Viewer
When a request is sent, Probe displays:
- **Status & Timing**: HTTP response status code, status text, and round-trip duration in milliseconds.
- **Headers & Payload**: Formatted, syntax-highlighted response body with one-click copy.
- **Database Queries**: Complete list of SQL queries triggered during the request, showing execution time and parameter bindings.
- **Logs & Exceptions**: In-flight logs and exception stack traces captured during request lifecycle.

### 5. Template Variables (`{{ variable }}`)
Any URL, header value, query parameter, or JSON body string can reference variables using double curly braces:
```
http://localhost:8000/api/users/{{ userId }}
Authorization: Bearer {{ apiToken }}
```
Variables can be managed dynamically from the environment settings panel or command palette.

### 6. Command Palette
Press `Cmd+K` or `Ctrl+K` to open the command palette:
- Filter and jump directly to any endpoint.
- Trigger actions: Send request (`Cmd+Enter`), open new tab, duplicate tab, or toggle dark theme.

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
