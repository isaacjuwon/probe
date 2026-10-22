<?php

declare(strict_types=1);

use Illuminate\Encryption\Encrypter;
use Isaacjuwon\Probe\Relay\CookieDecryptor;
use Symfony\Component\HttpFoundation\Cookie;

it('formats and passes through unencrypted cookies', function (): void {
    $decryptor = new CookieDecryptor;
    $cookie = new Cookie(name: 'plain_pref', value: 'dark_theme');

    $result = $decryptor->decryptResponseCookies(cookies: [$cookie]);

    expect($result)->toHaveCount(1)
        ->and($result[0]['name'])->toBe('plain_pref')
        ->and($result[0]['raw_value'])->toBe('dark_theme')
        ->and($result[0]['decrypted_value'])->toBe('dark_theme')
        ->and($result[0]['is_encrypted'])->toBeFalse();
});

it('decrypts encrypted cookies and json payloads', function (): void {
    $key = random_bytes(length: 32);
    $encrypter = new Encrypter(key: $key, cipher: 'AES-256-CBC');
    $decryptor = new CookieDecryptor(encrypter: $encrypter);

    $encryptedString = $encrypter->encrypt(value: 'secret_session_id', serialize: false);
    $encryptedJson = $encrypter->encrypt(value: json_encode(value: ['user_id' => 42, 'role' => 'admin']), serialize: false);

    $cookie1 = new Cookie(name: 'session_cookie', value: $encryptedString);
    $cookie2 = new Cookie(name: 'payload_cookie', value: $encryptedJson);

    $result = $decryptor->decryptResponseCookies(cookies: [$cookie1, $cookie2]);

    expect($result)->toHaveCount(2)
        ->and($result[0]['name'])->toBe('session_cookie')
        ->and($result[0]['is_encrypted'])->toBeTrue()
        ->and($result[0]['decrypted_value'])->toBe('secret_session_id')
        ->and($result[1]['name'])->toBe('payload_cookie')
        ->and($result[1]['is_encrypted'])->toBeTrue()
        ->and($result[1]['decrypted_value'])->toBe(['user_id' => 42, 'role' => 'admin']);
});

it('parses and decrypts Set-Cookie headers', function (): void {
    $key = random_bytes(length: 32);
    $encrypter = new Encrypter(key: $key, cipher: 'AES-256-CBC');
    $decryptor = new CookieDecryptor(encrypter: $encrypter);

    $encrypted = $encrypter->encrypt(value: 'cookie-value', serialize: false);
    $headers = [
        'Set-Cookie' => [
            "token={$encrypted}; path=/; httponly; samesite=lax",
        ],
    ];

    $result = $decryptor->decryptFromHeaders(headers: $headers);

    expect($result)->toHaveCount(1)
        ->and($result[0]['name'])->toBe('token')
        ->and($result[0]['is_encrypted'])->toBeTrue()
        ->and($result[0]['decrypted_value'])->toBe('cookie-value')
        ->and($result[0]['http_only'])->toBeTrue()
        ->and($result[0]['same_site'])->toBe('lax');
});
