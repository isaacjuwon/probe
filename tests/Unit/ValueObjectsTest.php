<?php

declare(strict_types=1);

use Isaacjuwon\Probe\Data\Endpoint;
use Isaacjuwon\Probe\Data\FieldSchema;
use Isaacjuwon\Probe\Data\RelayRequest;
use Isaacjuwon\Probe\Data\RelayResult;
use Isaacjuwon\Probe\Enums\AuthType;
use Isaacjuwon\Probe\Enums\FieldType;
use Isaacjuwon\Probe\Enums\HttpMethod;

it('serializes endpoint to array', function (): void {
    $field = new FieldSchema(
        name: 'username',
        type: FieldType::String,
        required: true,
        example: 'john',
    );

    $endpoint = new Endpoint(
        method: HttpMethod::Post,
        uri: 'api/login',
        name: 'login',
        action: 'AuthController@login',
        fields: ['username' => $field],
        auth: AuthType::None,
    );

    $array = $endpoint->toArray();

    expect($array['method'])->toBe('POST')
        ->and($array['uri'])->toBe('api/login')
        ->and($array['fields']['username']['name'])->toBe('username')
        ->and($array['fields']['username']['required'])->toBeTrue();
});

it('serializes relay request and relay result to array', function (): void {
    $request = new RelayRequest(
        method: HttpMethod::Get,
        url: 'http://localhost/api/test',
        headers: ['X-Custom' => 'val'],
        query: ['page' => 2],
        authType: AuthType::Bearer,
        authPayload: ['token' => 'abc'],
        timeoutSeconds: 15,
    );

    $reqArray = $request->toArray();
    expect($reqArray['method'])->toBe('GET')
        ->and($reqArray['url'])->toBe('http://localhost/api/test')
        ->and($reqArray['timeout_seconds'])->toBe(15);

    $result = new RelayResult(
        status: 200,
        headers: ['Content-Type' => 'application/json'],
        body: ['ok' => true],
        durationMs: 12.5,
        queries: [['sql' => 'select 1', 'time' => 1.2]],
    );

    $resArray = $result->toArray();
    expect($resArray['status'])->toBe(200)
        ->and($resArray['duration_ms'])->toBe(12.5)
        ->and($resArray['queries'])->toHaveCount(1);
});
