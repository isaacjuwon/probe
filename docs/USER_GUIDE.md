# Probe User Guide

A comprehensive guide for developers using **Probe** (`isaacjuwon/probe`) to test, inspect, and document Laravel APIs.

---

## Table of Contents

1. [Overview](#1-overview)
2. [Getting Started](#2-getting-started)
3. [The User Interface](#3-the-user-interface)
   - [Sidebar & Endpoint Tree](#sidebar--endpoint-tree)
   - [Tab Bar & State Persistence](#tab-bar--state-persistence)
   - [Request Panel](#request-panel)
   - [Code Editor & Example Generation](#code-editor--example-generation)
   - [Response Viewer & Telemetry](#response-viewer--telemetry)
   - [Command Palette](#command-palette)
4. [Authentication Strategies](#4-authentication-strategies)
5. [Template Variables & Environment Configuration](#5-template-variables--environment-configuration)
6. [Advanced Configuration Recipes](#6-advanced-configuration-recipes)
   - [Custom Route Mounting & Middleware](#custom-route-mounting--middleware)
   - [Staging & Multi-Tenant Authorization](#staging--multi-tenant-authorization)
   - [Allowed Hosts & Outbound Network Guards](#allowed-hosts--outbound-network-guards)
   - [Scribe & OpenAPI Synchronization](#scribe--openapi-synchronization)
7. [Artisan CLI Tools](#7-artisan-cli-tools)
8. [Troubleshooting & FAQ](#8-troubleshooting--faq)

---

## 1. Overview

Probe embeds an interactive, lightweight REST client into your Laravel app without adding database migrations, external server processes, or third-party cloud dependencies.

Everything runs on your local machine:
- **Routes & Rules**: Discovered in real-time by reflecting on your application router, FormRequests, and controller methods.
- **Relay Dispatcher**: Sends requests through Laravel's HTTP client or directly in-process.
- **Diagnostics Collector**: Intercepts executed SQL queries, bindings, execution times, logs, and `dump()` statements.
- **UI Engine**: Precompiled Svelte 5 application styled with the Ferrite dark theme.

---

## 2. Getting Started

### Installation

Add `isaacjuwon/probe` to your `composer.json` development dependencies:

```bash
composer require isaacjuwon/probe --dev
```

Publish the package configuration:

```bash
php artisan vendor:publish --tag="probe-config"
```

Verify that Probe assets are in place (under `public/vendor/probe`):

```bash
php artisan vendor:publish --tag="probe-assets" --force
```

### Accessing the Dashboard

Start your local development server:

```bash
php artisan serve
```

Visit:
```
http://localhost:8000/probe
```

By default, Probe is accessible whenever `APP_ENV` is set to `local` or `testing`.

---

## 3. The User Interface

### Sidebar & Endpoint Tree
- Located on the left side of the screen.
- Displays all discovered routes matching your configuration (`config('probe.route_filters')`).
- Endpoints are automatically grouped by resource or controller name (e.g. `Users`, `Posts`, `Orders`).
- **Filter Field**: Type any URI segment, controller action, or HTTP method (e.g. `POST`, `users`) to filter in real-time.
- Click an endpoint to open it in the current tab or click `+` to open in a new tab.

### Tab Bar & State Persistence
- Work with multiple endpoints side-by-side using the tab bar across the top.
- Unsent changes, query parameters, custom headers, and body drafts are marked with a dirty indicator (`*`).
- All active tabs and drafts are persisted into your browser's **IndexedDB**, surviving page reloads and browser restarts.

### Request Panel
Organized into 5 intuitive tabs:
1. **Path**: Automatically displayed when `{param}` variables exist in the URL (e.g. `/api/users/{id}`). Enter values to dynamically interpolate into the URL bar and preview.
2. **Params**: Key-value query parameters (`?sort=desc&page=2`).
3. **Headers**: HTTP headers with auto-completion for common content types (`application/json`, etc.).
4. **Auth**: Configure authentication credentials or 1-click user impersonation.
5. **Body**: CodeMirror request body editor with syntax highlighting and formatting.

### Dry Run Mode (Automatic Database Rollback)
Beside the **Send** button, toggle the **Dry Run** badge:
- Dispatches the request inside an isolated database transaction.
- When the request completes, all database changes (`INSERT`, `UPDATE`, `DELETE`) are **automatically rolled back**.
- Allows you to safely test destructive mutations in local development without corrupting test data.

### Code Editor & Example Generation
- Powered by **CodeMirror 6** with dark syntax highlighting.
- Automatic bracket pairing, syntax validation, and line numbering.
- **Magic Example Generation**: Probe analyzes your validation rules (e.g. `email`, `in:admin,user`, `min:18`) and pre-populates sample values automatically when you open an endpoint.
- **Prettify Button**: Automatically re-indents and formats JSON.

### Response Viewer & Telemetry
After clicking **Send** (`Cmd+Enter` / `Ctrl+Enter`):
- **Status & Duration Pill**: Displays HTTP response code (e.g. `200 OK`, `201 Created`, `422 Unprocessable`) and execution duration in milliseconds.
- **Copy as Pest Test**: 1-click button that inspects your request and response to generate an idiomatic Pest PHP feature test ready to paste into your test suite.
- **Validation Errors (422 Diffing)**: When an endpoint returns a `422 Unprocessable Entity`, a dedicated **Validation** tab lists every failing FormRequest rule and field.
- **Stream Events (SSE)**: For endpoints returning `text/event-stream`, parses events and displays a timeline view.
- **Database Queries**: A dedicated telemetry tab listing all SQL queries executed during the request lifecycle, complete with execution durations and parameter bindings.
- **Logs & Dumps**: Any `Log::info()` or `dump()` calls triggered during the controller execution.

### Command Palette
Press `Cmd+K` (macOS) or `Ctrl+K` (Windows / Linux) to open the Command Palette:
- Jump to any endpoint by typing its name or path.
- Trigger commands:
  - `Send Request`: Dispatches the current tab.
  - `New Request Tab`: Creates a blank request tab.
  - `Close Tab`: Closes the current active tab.
  - `Toggle Theme`: Switches between Ferrite dark and light themes.

---

## 4. Authentication Strategies

Probe includes built-in support for standard API authentication strategies in the **Auth** tab:

| Auth Type | Description | Configuration |
| :--- | :--- | :--- |
| **None** | No credentials attached. | Default |
| **Sanctum (Act As)** | 1-click user impersonation without manual login. | Pick an application user from the dropdown. |
| **Bearer Token** | Injects `Authorization: Bearer <token>`. | Paste API token or JWT. |
| **Basic Auth** | Base64 encodes username & password. | Provide username and password. |
| **API Key** | Injects a custom key/value header. | Specify header name (e.g. `X-API-KEY`) and key value. |

---

## 5. Template Variables & Environment Configuration

You can use template placeholders `{{ variable_name }}` anywhere in your request:
- In the URL: `http://localhost:8000/api/users/{{ userId }}`
- In Headers: `Authorization: Bearer {{ accessToken }}`
- In Query Strings: `?tenant={{ tenantId }}`
- In the JSON Body:
  ```json
  {
    "email": "{{ userEmail }}",
    "role": "admin"
  }
  ```

Probe automatically detects placeholders and interpolates active variable values before sending the request.

---

## 6. Advanced Configuration Recipes

### Custom Route Mounting & Middleware
In `config/probe.php`:

```php
// Mount at a custom path
'path' => 'developer/api-client',

// Add authentication middleware
'middleware' => ['web', 'auth:sanctum'],
```

### Staging & Multi-Tenant Authorization
To restrict dashboard access to specific team members or user roles:

```php
// app/Providers/AppServiceProvider.php
use App\Models\User;
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('viewProbe', function (?User $user = null): bool {
        if (app()->environment('local')) {
            return true;
        }

        return $user !== null && in_array($user->email, [
            'lead-dev@company.com',
            'qa-engineer@company.com',
        ], strict: true);
    });
}
```

### Allowed Hosts & Outbound Network Guards
By default, Probe only allows requests to the local application host. To allow relaying requests to external microservices or staging APIs, configure `allowed_hosts`:

```php
// config/probe.php
'allowed_hosts' => [
    'localhost',
    '127.0.0.1',
    'api.staging.mycompany.internal',
],
```

### Scribe & OpenAPI Synchronization
If your application uses Knuckles Scribe for documentation, Probe can automatically extract endpoint descriptions, parameter notes, and response samples:

```bash
php artisan probe:scribe-sync
```

---

## 7. Artisan CLI Tools

| Command | Description |
| :--- | :--- |
| `php artisan probe:install` | Runs the setup wizard, publishes config, and links assets. |
| `php artisan probe:export` | Dumps the discovered API catalog to JSON. Useful for CI checks and OpenAPI schema pipelines. |
| `php artisan probe:scribe-sync` | Synchronizes documentation from Scribe output files. |

---

## 8. Troubleshooting & FAQ

### 1. "403 Forbidden" when opening `/probe`
- Check `config('probe.allowed_environments')`. By default, it allows `['local', 'testing']`.
- If running on a staging environment or Docker container with `APP_ENV=production`, configure the `viewProbe` Gate in `AppServiceProvider`.

### 2. My API routes are not appearing in the sidebar
- Check `config('probe.route_filters')`. By default, Probe discovers routes that start with prefix `api` or use middleware `api`.
- If your routes use a different prefix (e.g. `v1/` or `internal/`), update `config/probe.php`:
  ```php
  'route_filters' => [
      'prefixes' => ['api', 'v1'],
      'middleware' => ['api', 'web'],
  ],
  ```

### 3. Frontend styling is missing or looks unstyled
- Re-publish public assets:
  ```bash
  php artisan vendor:publish --tag="probe-assets" --force
  ```
- Clear browser cache for `/probe`.

---

## License

Probe is open-source software licensed under the [MIT license](file:///c:/Extra/probe/LICENSE.md).
