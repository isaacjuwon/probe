<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Relay;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Isaacjuwon\Probe\Contracts\RelayTransport;
use Isaacjuwon\Probe\Data\RelayRequest;
use Isaacjuwon\Probe\Data\RelayResult;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

final class InProcessTransport implements RelayTransport
{
    public function __construct(
        private readonly Kernel $kernel,
        private readonly ?HostGuard $hostGuard = null,
        private readonly ?CookieDecryptor $cookieDecryptor = null,
    ) {}

    public function send(RelayRequest $request): RelayResult
    {
        if ($this->hostGuard !== null) {
            $this->hostGuard->ensureAllowed(url: $request->url);
        }

        $subRequest = $this->createSubRequest(request: $request);
        $shouldDryRun = $request->dryRun;

        if ($shouldDryRun) {
            DB::beginTransaction();
        }

        try {
            /** @var SymfonyResponse $response */
            $response = $this->kernel->handle(request: $subRequest);

            return $this->buildResult(response: $response, dryRun: $shouldDryRun);
        } catch (Throwable $e) {
            return $this->buildExceptionResult(exception: $e, dryRun: $shouldDryRun);
        } finally {
            if ($shouldDryRun) {
                DB::rollBack();
            }
        }
    }

    private function createSubRequest(RelayRequest $request): Request
    {
        $parsed = parse_url(url: $request->url);
        $path = $parsed['path'] ?? '/';
        $queryString = $parsed['query'] ?? '';

        $server = [
            'REQUEST_URI' => $path.($queryString !== '' ? '?'.$queryString : ''),
            'SCRIPT_NAME' => '/index.php',
            'PHP_SELF' => '/index.php',
            'SERVER_NAME' => $parsed['host'] ?? 'localhost',
            'SERVER_PORT' => $parsed['port'] ?? 80,
            'REQUEST_METHOD' => $request->method->value,
            'HTTP_ACCEPT' => $request->headers['Accept'] ?? $request->headers['accept'] ?? 'application/json',
        ];

        foreach ($request->headers as $name => $value) {
            $normalizedName = strtoupper(string: str_replace(search: '-', replace: '_', subject: $name));

            if ($normalizedName === 'CONTENT_TYPE') {
                $server['CONTENT_TYPE'] = $value;
            } elseif ($normalizedName === 'CONTENT_LENGTH') {
                $server['CONTENT_LENGTH'] = $value;
            } else {
                $server['HTTP_'.$normalizedName] = $value;
            }
        }

        $content = null;

        if ($request->body !== null) {
            $content = is_string(value: $request->body)
                ? $request->body
                : json_encode(value: $request->body, flags: JSON_THROW_ON_ERROR);

            if (! isset($server['CONTENT_TYPE'])) {
                $server['CONTENT_TYPE'] = 'application/json';
            }
        }

        $cookies = [];
        $cookieHeader = $request->headers['Cookie'] ?? $request->headers['cookie'] ?? null;

        if (is_string(value: $cookieHeader)) {
            foreach (explode(separator: ';', string: $cookieHeader) as $part) {
                $pieces = explode(separator: '=', string: trim(string: $part), limit: 2);

                if (count(value: $pieces) === 2) {
                    $cookies[trim(string: $pieces[0])] = urldecode(string: trim(string: $pieces[1]));
                }
            }
        }

        return Request::create(
            uri: $request->url,
            method: $request->method->value,
            parameters: $request->query,
            cookies: $cookies,
            files: [],
            server: $server,
            content: $content,
        );
    }

    private function buildResult(SymfonyResponse $response, bool $dryRun): RelayResult
    {
        $rawContent = $response->getContent();
        $body = null;

        if ($rawContent !== false && $rawContent !== '') {
            try {
                $body = json_decode(json: $rawContent, associative: true, flags: JSON_THROW_ON_ERROR);
            } catch (Throwable) {
                $body = $rawContent;
            }
        }

        /** @var array<string, mixed> $headers */
        $headers = $response->headers->all();

        /** @var list<Cookie> $rawCookies */
        $rawCookies = $response->headers->getCookies();
        $cookies = $this->cookieDecryptor !== null
            ? $this->cookieDecryptor->decryptResponseCookies(cookies: $rawCookies)
            : [];

        return new RelayResult(
            status: $response->getStatusCode(),
            headers: $headers,
            body: $body,
            dryRun: $dryRun,
            cookies: $cookies,
        );
    }

    private function buildExceptionResult(Throwable $exception, bool $dryRun): RelayResult
    {
        return new RelayResult(
            status: 500,
            headers: [],
            body: ['error' => $exception->getMessage()],
            exceptions: [
                [
                    'class' => $exception::class,
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ],
            ],
            dryRun: $dryRun,
        );
    }
}
