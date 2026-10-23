# Probe User Guide

A comprehensive guide for developers using **Probe** (`isaacjuwon/probe`) to test, inspect, and document Laravel APIs.

---

## Table of Contents

1. [Overview](#1-overview)
2. [Getting Started](#2-getting-started)
3. [The User Interface](#3-the-user-interface)
   - [Top Navigation & Version Switcher](#top-navigation--version-switcher)
   - [Primary Activity Rail (Sidebar)](#primary-activity-rail-sidebar)
   - [Sidebar & Endpoint Tree](#sidebar--endpoint-tree)
   - [Request Panel](#request-panel)
   - [1-Click Auth Resolution Banner](#1-click-auth-resolution-banner)
   - [Dry Run Mode (Automatic Database Rollback)](#dry-run-mode-automatic-database-rollback)
   - [Code Editor & Example Generation](#code-editor--example-generation)
   - [Response Viewer & Telemetry](#response-viewer--telemetry)
   - [Command Palette](#command-palette)
4. [API Versioning](#4-api-versioning)
   - [How Versions Are Detected](#how-versions-are-detected)
   - [Smart Resource Grouping](#smart-resource-grouping)
   - [Top Header Version Switcher](#top-header-version-switcher)
5. [Authentication Strategies](#5-authentication-strategies)
   - [Supported Schemes & Visual Badges](#supported-schemes--visual-badges)
   - [Interactive Auth Filter Pills](#interactive-auth-filter-pills)
   - [In-line Auth Resolution](#in-line-auth-resolution)
6. [Template Variables & Environment Configuration](#6-template-variables--environment-configuration)
7. [Advanced Configuration Recipes](#7-advanced-configuration-recipes)
   - [Custom Route Mounting & Middleware](#custom-route-mounting--middleware)
   - [Staging & Multi-Tenant Authorization](#staging--multi-tenant-authorization)
   - [Allowed Hosts & Outbound Network Guards](#allowed-hosts--outbound-network-guards)
   - [Scribe & OpenAPI Synchronization](#scribe--openapi-synchronization)
8. [Artisan CLI Tools](#8-artisan-cli-tools)
9. [Troubleshooting & FAQ](#9-troubleshooting--faq)

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

### Top Navigation & Version Switcher
- **API Version Switcher**: When your application contains versioned endpoints (e.g. `v1`, `v2`), a global switcher appears in the top navigation bar (`All Versions`, `V1`, `V2`, `Unversioned`). Selecting a version instantly scopes the entire Explorer, and the choice is persisted across browser reloads via `localStorage`.
- **Environment Indicator**: Displays the active Laravel environment (`local`, `testing`, `staging`).
- **Command Palette (`Cmd+K` / `Ctrl+K`)**: Quick fuzzy-search for any endpoint or action.

### Primary Activity Rail (Sidebar)
The collapsible left rail provides one-click access to Probe's developer tool suite:
- **Endpoints Explorer**: Hierarchical API route tree.
- **Relay History**: Chronological list of sent requests with status codes and execution times.
- **Collections**: Saved requests and reusable API testing suites.
- **Telemetry**: Real-time SQL query profiling, slow query detection, and application logs.
- **User Impersonation**: One-click Sanctum persona switching across all active tabs.
- **Cookies**: Decrypted cookie inspector showing raw, decrypted, and parsed JSON values.
- **Environment & Variables**: Template variable manager with live interpolation preview.
- **GitHub Repository Link**: Direct link to the open-source repository at `https://github.com/isaacjuwon/probe`.

### Sidebar & Endpoint Tree
- **Scope & Version Filters**: Filter between API routes (`api/*`), Web routes, and specific API versions (`v1`, `v2`).
- **Grouping Modes**: Toggle between **Resource** (e.g. `Auth`, `Orders`, `Products`), **Controller** (e.g. `OrderController`), or **Version** (`V1`, `V2`, `Unversioned`).
- **Smart Resource Grouping**: Probe recognizes version prefixes in URIs (like `/api/v1/orders`) and strips them from the group name so that your endpoints are cleanly organized under their actual domain resources (`Orders`) rather than being dumped into monolithic `"V1"` folders.
- **Visual Route Badges**:
  - HTTP Verb badges (`GET`, `POST`, `PUT`, `PATCH`, `DELETE`).
  - Route Version badges (`V1`, `V2`).
  - Clean, color-coded Authentication Icon Badges (🔒 Sanctum, 🔑 API Key, 👤 Basic, 🛡️ Bearer, 🍪 Session).
- **Interactive Auth Filter Pills**: Filter endpoints by their authentication scheme with live counts: `All`, `🔒`, `🔑`, `👤`, `🌐`.

### Request Panel
Organized into 5 intuitive tabs:
1. **Path**: Automatically displayed when `{param}` variables exist in the URL (e.g. `/api/users/{id}`). Enter values to dynamically interpolate into the URL bar and preview.
2. **Params**: Key-value query parameters (`?sort=desc&page=2`).
3. **Headers**: HTTP headers with auto-completion for common content types (`application/json`, etc.).
4. **Auth**: Configure authentication credentials or 1-click user impersonation.
5. **Body**: CodeMirror request body editor with syntax highlighting and formatting.

### 1-Click Auth Resolution Banner
Directly below the URL bar, Probe displays an intelligent Auth Status Banner:
- **API Key Required**: Displays `Requires API Key (X-API-Key):` with an inline input so you can paste your key immediately without switching tabs.
- **Sanctum Impersonation Required**: Displays a user dropdown to choose an application user to impersonate in 1 click.
- **Auth Mismatch Warnings**: If a route expects an API Key but your tab was configured for Sanctum (or None), Probe alerts you and provides a 1-click `[Switch to API Key]` resolution button.

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
- **Cookies**: View response cookies and session state.
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

## 4. API Versioning

Probe adheres to standard REST and Laravel API versioning conventions with zero manual configuration required.

### How Versions Are Detected
Probe reflects on your application routes in real-time across multiple standard patterns:
1. **URI Path Segments**: Matches version segments such as `/api/v1/...`, `/api/v2.1/...`, `/v1-beta/...`, and date-based versions like `/api/2026-10-01/...`.
2. **Route Prefixes**: Detects versions defined on route groups (`Route::prefix('v1')->group(...)`).
3. **Route Names**: Detects prefixes on route names (e.g. `v1.orders.index`, `api.v2.users`).
4. **Controller Namespaces**: Inspects controller namespaces (e.g. `App\Http\Controllers\Api\V1\UserController` -> `v1`).
5. **Route Middleware**: Detects version middleware (e.g. `version:v1`, `api-version:v2`).
6. **Controller DocBlocks**: Parses `@version v1` or `@version 2.0` in action docstrings.
7. **Imported Specs**: Auto-detects versions from OpenAPI 3.x and Postman collections.

### Smart Resource Grouping
In traditional API explorers, versioned routes often end up lumped into monolithic `"V1"` or `"V2"` folders, scattering your actual business resources. Probe solves this with **Smart Resource Grouping**:
- When grouping by **Resource**, Probe strips the leading version identifier (e.g. `/api/v1/orders` and `/api/v2/orders` both map cleanly under the **Orders** group).
- Each endpoint within the group prominently displays a `V1` or `V2` badge on its row.
- You can also switch grouping modes to **Controller** or **Version** depending on your team's workflow.

### Top Header Version Switcher
When versioned routes exist in your app, Probe renders an interactive dropdown directly in the global top header bar:
- Displays all detected versions with live route counts (e.g., `All Versions (21)`, `v1 (14)`, `v2 (5)`, `unversioned (2)`).
- Selecting a version immediately filters the entire sidebar route tree to that version.
- Your selection is remembered across page refreshes via browser `localStorage` (`probe_version_filter`).

---

## 5. Authentication Strategies

Probe provides seamless, zero-friction authentication testing right from the dashboard.

### Supported Schemes & Visual Badges
Probe detects route authentication schemes and renders minimal, color-coded icons on route rows:

| Auth Scheme | Icon | Detection Criteria | Behavior |
| :--- | :---: | :--- | :--- |
| **None (Public)** | *(None)* | Default when no auth middleware is present. | Dispatched without auth headers. Clean and quiet visual design. |
| **Sanctum (Act As)** | 🔒 | `auth:sanctum` middleware, `@authenticated` docblock. | Generates an in-memory Sanctum token for the chosen user without requiring manual login. |
| **API Key** | 🔑 | `api_key` middleware, `ApiKeyProtectedController`, `@header X-API-Key`. | Injects custom key/value header (default `X-API-Key`) into the request. |
| **Basic Auth** | 👤 | `auth:basic` middleware, `BasicAuth...` controller. | Base64 encodes username & password into `Authorization: Basic`. |
| **Bearer Token** | 🛡️ | `auth:api`, `bearer` middleware. | Injects `Authorization: Bearer <token>`. |
| **Session** | 🍪 | Web middleware / session cookie. | Forwards current browser session cookies and CSRF tokens automatically. |

### Interactive Auth Filter Pills
At the top of the sidebar, filter buttons with icons and route counts let you inspect routes by security posture:
- `All (21)` — All registered application routes.
- `🔒 13` — Routes requiring Sanctum authentication.
- `🔑 2` — Routes requiring an API Key.
- `👤 1` — Routes protected by HTTP Basic Auth.
- `🌐 5` — Public routes requiring no authentication.

### In-line Auth Resolution
Never switch back and forth between tabs just to satisfy authentication:
- **Instant API Key Input**: When selecting an API Key-protected route, an inline header input (`Requires API Key: X-API-Key`) appears directly below the URL bar.
- **Sanctum Persona Switcher**: When selecting a Sanctum route, a persona dropdown appears directly beneath the URL bar allowing you to select any database user and immediately test authenticated requests.
- **Mismatch Warnings**: If an endpoint expects an API Key but your active tab is configured for Sanctum (or None), Probe shows a warning with a 1-click `[Switch to API Key]` resolution button.

---

## 6. Template Variables & Environment Configuration

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

## 7. Advanced Configuration Recipes

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

## 8. Artisan CLI Tools

| Command | Description |
| :--- | :--- |
| `php artisan probe:install` | Runs the setup wizard, publishes config, and links assets. |
| `php artisan probe:export` | Dumps the discovered API catalog to JSON. Useful for CI checks and OpenAPI schema pipelines. |
| `php artisan probe:scribe-sync` | Synchronizes documentation from Scribe output files. |

---

## 9. Troubleshooting & FAQ

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
