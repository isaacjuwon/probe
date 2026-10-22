<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Isaacjuwon\Probe\Storage\FileRequestRepository;

beforeEach(function (): void {
    Storage::fake(disk: 'local');
});

it('saves, retrieves, lists and deletes requests from file storage', function (): void {
    $repository = new FileRequestRepository(disk: 'local', basePath: 'probe-tests');

    $saveSuccess = $repository->save(
        id: 'req_123',
        data: [
            'method' => 'GET',
            'url' => 'http://localhost/api/users',
            'headers' => [
                'Authorization' => 'Bearer secret-jwt-token',
                'Accept' => 'application/json',
            ],
            'auth_payload' => [
                'token' => 'secret-jwt-token',
                'password' => 'super-secret',
            ],
        ],
    );

    expect($saveSuccess)->toBeTrue();

    $found = $repository->find(id: 'req_123');
    expect($found)->not->toBeNull()
        ->and($found['id'])->toBe('req_123')
        ->and($found['headers']['Authorization'])->toBe('Bearer ********')
        ->and($found['auth_payload']['token'])->toBe('********')
        ->and($found['auth_payload']['password'])->toBe('********');

    $all = $repository->all();
    expect($all->count())->toBe(1);

    $deleteSuccess = $repository->delete(id: 'req_123');
    expect($deleteSuccess)->toBeTrue()
        ->and($repository->find(id: 'req_123'))->toBeNull();
});
