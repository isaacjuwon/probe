<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Relay;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Symfony\Component\HttpFoundation\Cookie;
use Throwable;

final class CookieDecryptor
{
    public function __construct(
        private readonly ?Encrypter $encrypter = null,
    ) {}

    /**
     * @param  list<Cookie>  $cookies
     * @return list<array<string, mixed>>
     */
    public function decryptResponseCookies(array $cookies): array
    {
        $result = [];

        foreach ($cookies as $cookie) {
            $result[] = $this->formatCookie(
                name: $cookie->getName(),
                value: $cookie->getValue(),
                domain: $cookie->getDomain(),
                path: $cookie->getPath(),
                expires: $cookie->getExpiresTime(),
                httpOnly: $cookie->isHttpOnly(),
                secure: $cookie->isSecure(),
                sameSite: $cookie->getSameSite(),
            );
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return list<array<string, mixed>>
     */
    public function decryptFromHeaders(array $headers): array
    {
        $rawCookies = [];
        $setCookies = $headers['set-cookie'] ?? $headers['Set-Cookie'] ?? [];

        if (is_string(value: $setCookies)) {
            $setCookies = [$setCookies];
        }

        if (is_array(value: $setCookies)) {
            foreach ($setCookies as $headerString) {
                if (is_string(value: $headerString)) {
                    $rawCookies[] = Cookie::fromString(cookie: $headerString);
                }
            }
        }

        return $this->decryptResponseCookies(cookies: $rawCookies);
    }

    /**
     * @return array<string, mixed>
     */
    public function formatCookie(
        string $name,
        ?string $value,
        ?string $domain = null,
        string $path = '/',
        int $expires = 0,
        bool $httpOnly = false,
        bool $secure = false,
        ?string $sameSite = null,
    ): array {
        $decryptedValue = $value;
        $isEncrypted = false;

        if ($value !== null && $value !== '') {
            $decrypted = $this->tryDecrypt(value: $value);

            if ($decrypted !== null) {
                $isEncrypted = true;
                $decryptedValue = $decrypted;
            }
        }

        return [
            'name' => $name,
            'raw_value' => $value,
            'decrypted_value' => $decryptedValue,
            'is_encrypted' => $isEncrypted,
            'domain' => $domain,
            'path' => $path,
            'expires' => $expires,
            'http_only' => $httpOnly,
            'secure' => $secure,
            'same_site' => $sameSite,
        ];
    }

    private function tryDecrypt(string $value): mixed
    {
        if ($this->encrypter === null) {
            return null;
        }

        try {
            $decrypted = $this->encrypter->decrypt(payload: $value, unserialize: false);

            if (is_string(value: $decrypted)) {
                try {
                    return json_decode(json: $decrypted, associative: true, flags: JSON_THROW_ON_ERROR);
                } catch (Throwable) {
                    return $decrypted;
                }
            }

            return $decrypted;
        } catch (DecryptException) {
            try {
                return $this->encrypter->decrypt(payload: $value, unserialize: true);
            } catch (Throwable) {
                return null;
            }
        } catch (Throwable) {
            return null;
        }
    }
}
