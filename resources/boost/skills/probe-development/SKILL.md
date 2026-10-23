---
name: probe-development
description: "Configure, customize, and test the Probe in-app REST client package in Laravel applications."
license: MIT
metadata:
  author: isaacjuwon
---

# Probe Development

Use this skill when a Laravel application needs to install, configure, customize authorization for, or test the `isaacjuwon/probe` package.

## Primary Goal

Apply the `isaacjuwon/probe` package's public API, configuration, and testing hooks in the smallest correct way.

## Workflow

### 1. Installation & Publishing

- Install as a development dependency:
  ```bash
  composer require isaacjuwon/probe --dev
  ```
- Publish configuration if custom route prefix, middleware, or storage settings are required:
  ```bash
  php artisan vendor:publish --tag="probe-config"
  ```
- Public assets are automatically published during `composer install` / `composer update`, but can be refreshed using:
  ```bash
  php artisan vendor:publish --tag="probe-assets" --force
  ```

### 2. Authorization Customization

By default, Probe restricts access to `local` and `testing` environments via the `viewProbe` Gate.

To authorize specific users or environments, define the `viewProbe` ability in `AppServiceProvider`:

```php
use App\Models\User;
use Illuminate\Support\Facades\Gate;

Gate::define('viewProbe', fn (?User $user = null): bool => 
    app()->environment(['local', 'testing']) || ($user !== null && $user->is_developer)
);
```

### 3. Endpoint Discovery & Validation Rules

Probe automatically discovers routes matching `config('probe.route_filters')` (default: prefix `api`, middleware `api`).

To ensure endpoints are discovered with rich body schemas:
- Typehint `FormRequest` classes in controller action parameters.
- Or use inline `$request->validate([...])`.
- Or use Spatie Laravel Data objects.

Probe parses rules (`required`, `email`, `in:a,b`, `numeric`, `min`, `max`, `nullable`, `boolean`, `array`) and automatically derives sample values.

### 4. API Versioning & Route Grouping

Probe automatically identifies API versions across multiple standard conventions:
- URI segments (e.g. `/api/v1/...`, `/api/2026-10-01/...`)
- Route group prefixes (`Route::prefix('v1')`)
- Route names (`v1.orders.index`)
- Controller namespaces (`App\Http\Controllers\Api\V1\...`)
- Middleware (`version:v1`)
- DocBlocks (`@version v1`)

In **Resource Grouping** mode, version prefixes are automatically stripped from group names so routes group under business entities (`Orders`, `Products`), with `V1`/`V2` badges on individual endpoints. A global version switcher in the dashboard header allows filtering by version.

### 5. Authentication Strategies

Probe detects route protection from middleware (`auth:sanctum`, `api_key`, `auth:basic`, `auth:api`, web session cookies) and controller docblocks:
- Minimal color-coded icon badges (🔒, 🔑, 👤, 🛡️, 🍪) on route rows.
- 1-click Sanctum persona switcher in the dashboard to impersonate users without logging in.
- In-line API key headers input right below the URL bar.

### 6. Relay Transports & Dry Run Mode

- **In-Process Kernel Dispatch (Default)**: Probe dispatches requests directly through Laravel's HTTP Kernel (`$kernel->handle($subRequest)`) without loopback sockets, avoiding single-threaded worker deadlocks when running `php artisan serve`.
- **Dry Run Mode**: Wrap any relay request in an isolated database transaction that automatically rolls back when the request completes:
  ```php
  $response = $this->postJson('/probe/relay', [
      'method' => 'POST',
      'url' => 'http://localhost/api/users',
      'body' => ['email' => 'test@example.com'],
      'dry_run' => true,
  ]);
  ```

### 7. Running Commands

- Export API catalog: `php artisan probe:export > api.json`
- Synchronize Scribe documentation: `php artisan probe:scribe-sync`

### 8. Testing Applications Using Probe

- When testing Probe features in application test suites, use `Illuminate\Support\Facades\Http::fake()` to stub relayed requests:

```php
use Illuminate\Support\Facades\Http;

it('relays requests through probe', function () {
    Http::fake([
        'http://localhost/api/users*' => Http::response(
            body: ['data' => []],
            status: 200,
        ),
    ]);

    $this->postJson('/probe/relay', [
        'method' => 'GET',
        'url' => 'http://localhost/api/users',
        'headers' => ['Accept' => 'application/json'],
        'body' => '',
        'auth' => ['type' => 'none'],
    ])->assertOk()->assertJsonPath('data.status', 200);
});
```

## References

- Service Provider: `Isaacjuwon\Probe\ProbeServiceProvider`
- Configuration: `config/probe.php`
- Web Dashboard Route: `/probe` (`probe.index`)
- API Endpoints:
  - `GET /probe/endpoints` (`probe.endpoints`)
  - `POST /probe/relay` (`probe.relay`)
  - `POST /probe/requests` (`probe.requests.save`)
- Gate Ability: `viewProbe`

## Anti-patterns

- Do not run database migrations for Probe (Probe requires 0 migrations).
- Do not instantiate internal transports or actions with `new`; resolve via Laravel service container or contracts (`Isaacjuwon\Probe\Contracts\RelayTransport`, etc.).
- Do not make unmocked network HTTP calls in CI tests; always use `Http::fake()`.
- Do not remove secret masking from saved request persistence.
