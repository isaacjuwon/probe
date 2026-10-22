<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Relay;

use Illuminate\Support\Facades\Http;
use Isaacjuwon\Probe\Contracts\RelayTransport;
use Isaacjuwon\Probe\Data\RelayRequest;
use Isaacjuwon\Probe\Data\RelayResult;
use Throwable;

final class HttpTransport implements RelayTransport
{
    public function __construct(
        private readonly ?HostGuard $hostGuard = null,
        private readonly ?CookieDecryptor $cookieDecryptor = null,
    ) {}

    public function send(RelayRequest $request): RelayResult
    {
        if ($this->hostGuard !== null) {
            $this->hostGuard->ensureAllowed(url: $request->url);
        }

        $client = Http::timeout(seconds: $request->timeoutSeconds)
            ->withHeaders(headers: $request->headers);

        $options = [];

        if ($request->query !== []) {
            $options['query'] = $request->query;
        }

        if ($request->body !== null) {
            if (is_array(value: $request->body)) {
                $options['json'] = $request->body;
            } elseif (is_string(value: $request->body)) {
                $options['body'] = $request->body;
            }
        }

        try {
            $response = $client->send(
                method: $request->method->value,
                url: $request->url,
                options: $options,
            );

            $body = $response->json();

            if ($body === null && $response->body() !== '') {
                $body = $response->body();
            }

            $cookies = $this->cookieDecryptor !== null
                ? $this->cookieDecryptor->decryptFromHeaders(headers: $response->headers())
                : [];

            $dumps = [];
            $rawBody = $response->body();

            if (str_contains(haystack: $rawBody, needle: 'sf-dump')) {
                $dumps[] = $rawBody;
            }

            return new RelayResult(
                status: $response->status(),
                headers: $response->headers(),
                body: $body,
                dumps: $dumps,
                cookies: $cookies,
            );
        } catch (Throwable $e) {
            return new RelayResult(
                status: 500,
                headers: [],
                body: ['error' => $e->getMessage()],
                exceptions: [
                    [
                        'class' => get_class(object: $e),
                        'message' => $e->getMessage(),
                        'code' => $e->getCode(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ],
                ],
            );
        }
    }
}
