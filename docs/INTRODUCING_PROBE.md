# Introducing Probe: The Zero-Config, Laravel-Aware API Playground Built Right Into Your App

> Stop wrestling with Postman sync fatigue, manual bearer token juggling, and corrupted local databases. Probe embeds an intelligent REST client and telemetry inspector directly inside your Laravel application.

---

Every Laravel developer knows this routine:

You create a controller, wire up a new route, and write a thorough `FormRequest` with validation rules. Then, you switch windows:

1. Open Postman, Insomnia, or Bruno.
2. Manually type `http://localhost:8000/api/v1/orders`.
3. Open Tinker or query the database to generate an auth token for user `#42`.
4. Paste the token into an `Authorization: Bearer ...` header.
5. Manually reconstruct the JSON payload by alt-tabbing back and forth to your `FormRequest` rules.
6. Hit Send.
7. Realize you updated a database row you didn't want to change, and now have to re-run `php artisan migrate:fresh --seed`.

Desktop API clients are great for team collections, but for day-to-day local development, they exist in a silo. They have no idea what routes exist in your Laravel application, what validation rules your `FormRequest` expects, what SQL queries your controller just ran, or what user you want to test as.

That is why we built **[Probe](https://github.com/isaacjuwon/probe)** (`isaacjuwon/probe`).

---

## What is Probe?

Probe is a developer-only, in-browser REST client and API diagnostics tool embedded directly into your Laravel application.

```bash
composer require isaacjuwon/probe --dev
```

Open `http://localhost:8000/probe` in your browser. 

There are:
- **Zero database migrations** to run.
- **Zero external servers or cloud logins** required.
- **Zero npm/node build steps** in your application (assets come precompiled).

Probe runs entirely on your local machine, reflecting directly on your Laravel routes, controllers, and validation rules in real-time.

```
+--------------------------------------------------------------------------------------------------+
|  [PROBE] [LOCAL]   🌿 [All Versions (21) ▾]                            Search (Cmd+K)    GitHub  |
+----------------------+---------------------------------------------------------------------------+
| 🔍 Filter routes...   | POST /api/v1/orders                                           [Send] [DRY]|
|                      +---------------------------------------------------------------------------+
| 🔒 13  🔑 2  👤 1  🌐 | 🔒 Requires Sanctum Auth: [Act as: Alice Johnson (alice@example.com) ▾]  |
|                      +---------------------------------------------------------------------------+
| ▼ Orders             | Path | Params | Headers | Auth | Body                                    |
|   GET  /orders       | {                                                                         |
|   POST /orders  [V1] |   "product_id": 14,                                                       |
|   PUT  /{id}    [V1] |   "quantity": 2,                                                          |
|                      |   "coupon_code": "DISCOUNT20"                                             |
| ▼ Products           | }                                                                         |
|   GET  /products [V1]|---------------------------------------------------------------------------|
|   POST /products [V1]| 201 Created • 42ms                                   [Copy as Pest Test]  |
|                      | Body | Headers | Queries (3) | Cookies | Telemetry                        |
+----------------------+---------------------------------------------------------------------------+
```

---

## Key Superpowers That Set Probe Apart

### 1. Zero-Config FormRequest Reflection & Magic Example Payloads

Probe doesn't just list your routes; it inspects your controller methods and typehinted `FormRequest` classes in real-time.

It parses rules like `required`, `email`, `in:active,inactive`, `min:18`, and nested array validation. When you click an endpoint, Probe automatically populates the CodeMirror JSON body editor with realistic sample data derived from your validation rules. If you change a rule in PHP, it updates in Probe instantly.

### 2. Standard API Versioning That Actually Makes Sense

Most API explorers either ignore API versioning completely or dump all versioned endpoints into monolithic `"V1"` and `"V2"` folders, completely fracturing your domain resources.

Probe implements **Standard API Versioning** out of the box:
- Automatically detects versions from URI path segments (`/api/v1/...`, `/api/2026-10-01/...`), route group prefixes (`Route::prefix('v1')`), route names (`v1.orders.index`), controller namespaces (`App\Http\Controllers\Api\V1`), middleware (`version:v1`), and `@version` docblocks.
- **Smart Resource Grouping**: Strips the version prefix when organizing groups. Your `/api/v1/orders` and `/api/v2/orders` endpoints are cleanly grouped under **Orders**, with clear `V1` and `V2` badges on each route row.
- **Top Header Version Switcher**: A global dropdown in the header lets you instantly scope the entire explorer to `All Versions`, `v1`, `v2`, or `unversioned`. Your preference is persisted via `localStorage`.

### 3. One-Click Sanctum Impersonation & In-line Auth Resolution

Testing authenticated routes in Postman is notoriously tedious. Probe makes it frictionless:

- **Minimal Icon Badges**: Route rows display clean, color-coded icons (🔒 Sanctum, 🔑 API Key, 👤 Basic, 🛡️ Bearer, 🍪 Session). Public routes remain badge-free to eliminate visual clutter.
- **1-Click Sanctum Persona Switcher**: When you open a route protected by `auth:sanctum`, Probe renders an inline user dropdown right below the URL bar. Select any database user, and Probe dispatches the request authenticated as that user on the fly. No tokens to generate, copy, or expire.
- **Inline API Key Prompts**: On routes protected by an API key header (`X-API-Key`), an inline input appears directly beneath the URL bar so you can paste the key immediately without digging through headers tabs.
- **Auth Mismatch Detection**: If your active tab is configured with `None` or `Bearer` but the selected endpoint requires `API Key`, Probe warns you and provides a 1-click `[Switch to API Key]` resolution button.

### 4. Dry Run Mode: Test Destructive Mutations Without Corrupting Test Data

How many times have you tested a `DELETE /api/users/1` or `POST /api/orders/cancel` endpoint, only to realize you just corrupted the seed data your frontend team was using?

With Probe, you simply flip the **Dry Run** switch next to the **Send** button:
- Probe executes the request inside a database transaction.
- Your controller executes normally, queries run, and the real JSON response is returned.
- Upon completion, the database transaction is **automatically rolled back**.

You get the real HTTP status and payload, but your database remains completely untouched.

### 5. Instant Pest Feature Test Generator

Once you test an endpoint and verify the JSON response looks right, you usually have to write a feature test.

Probe automates this with the **Copy as Pest Test** button in the response viewer. With one click, Probe generates an idiomatic Pest PHP test matching your exact request parameters, headers, and expected response shape:

```php
it('creates an order successfully', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/v1/orders', [
            'product_id' => 14,
            'quantity' => 2,
            'coupon_code' => 'DISCOUNT20',
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'processing');
});
```

Paste it into `tests/Feature/OrderTest.php` and you're done.

### 6. Built-in SQL Telemetry & Query Profiling

Probe captures the complete database lifecycle of every request:
- Exact SQL queries executed with parameter bindings substituted.
- Execution duration per query.
- Identification of slow queries and potential N+1 loops.
- Captured application logs (`Log::info()`) and `dump()` output.

### 7. Decrypted Cookie Inspector

Debugging encrypted session cookies, remember tokens, and signed cookies in browser DevTools often means staring at unintelligible base64 strings. Probe features a dedicated **Cookies** inspector that decrypts Laravel cookies in real-time, displaying raw, decrypted, and parsed JSON structures side-by-side.

### 8. In-Process Kernel Dispatch (No Server Deadlocks)

When testing local APIs with `php artisan serve`, making an HTTP loopback call from inside PHP can deadlock because PHP's built-in development server is single-threaded.

Probe solves this by offering **In-Process Kernel Dispatch**. Requests are routed directly through Laravel's HTTP Kernel (`$kernel->handle($subRequest)`) in memory without opening a network socket. It is blazing fast and immune to server concurrency deadlocks.

---

## Getting Started in 60 Seconds

### Step 1: Install via Composer

```bash
composer require isaacjuwon/probe --dev
```

### Step 2: Publish Assets & Configuration (Optional)

Probe publishes its assets automatically, but you can publish configuration anytime:

```bash
php artisan vendor:publish --tag="probe-config"
```

### Step 3: Open the Dashboard

Start your Laravel development server:

```bash
php artisan serve
```

Visit:
```
http://localhost:8000/probe
```

That's it! Probe immediately discovers all your routes, schemas, and versions.

---

## Security by Default

Probe is strictly designed for development environments:
- By default, it is **only** accessible when `APP_ENV` is `local` or `testing`.
- On any other environment, it returns a `403 Forbidden`.
- Authorization is managed by Laravel's standard authorization Gate (`viewProbe`).

If you wish to allow dashboard access on a staging or preview server for specific developers:

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
            'developer@company.com',
            'qa-lead@company.com',
        ], strict: true);
    });
}
```

---

## Keyboard-Centric Workflow

Probe is designed to keep your hands on the keyboard:
- `Cmd+K` / `Ctrl+K`: Global command palette to fuzzy-search any route or action.
- `Cmd+Enter` / `Ctrl+Enter`: Dispatch the active request.
- `Cmd+T` / `Ctrl+T`: Open a new blank request tab.
- `Cmd+W` / `Ctrl+W`: Close the active request tab.
- `{{ variable }}`: Dynamic variable substitution in URLs, headers, and body payloads.

---

## Open Source & Ready for Laravel 12 & 13

Probe is open-source software licensed under the MIT License. It supports:
- **PHP**: `^8.3` and `^8.4`
- **Laravel**: `^12.0` and `^13.0`

Check out the code, star the repository, or contribute on GitHub:
👉 **[https://github.com/isaacjuwon/probe](https://github.com/isaacjuwon/probe)**

Give Probe a try on your current Laravel project and experience the joy of testing APIs without leaving your app!
