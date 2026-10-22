<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Storage;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Isaacjuwon\Probe\Contracts\RequestRepository;

final class FileRequestRepository implements RequestRepository
{
    public function __construct(
        private readonly string $disk = 'local',
        private readonly string $basePath = 'probe',
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function all(): Collection
    {
        $storage = Storage::disk(name: $this->disk);

        if (! $storage->exists(path: $this->basePath)) {
            return new Collection;
        }

        $files = $storage->allFiles(directory: $this->basePath);
        $records = [];

        foreach ($files as $file) {
            if (! str_ends_with(haystack: $file, needle: '.json')) {
                continue;
            }

            $content = $storage->get(path: $file);

            if ($content === null || $content === '') {
                continue;
            }

            $decoded = json_decode(json: $content, associative: true);

            if (is_array(value: $decoded)) {
                /** @var array<string, mixed> $decoded */
                $records[] = $decoded;
            }
        }

        return new Collection(items: $records);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $id): ?array
    {
        $path = $this->filePath(id: $id);
        $storage = Storage::disk(name: $this->disk);

        if (! $storage->exists(path: $path)) {
            return null;
        }

        $content = $storage->get(path: $path);

        if ($content === null || $content === '') {
            return null;
        }

        $decoded = json_decode(json: $content, associative: true);

        return is_array(value: $decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(string $id, array $data): bool
    {
        $data['id'] = $id;
        $data['updated_at'] = date(format: 'Y-m-d H:i:s');

        $masked = $this->maskSecrets(data: $data);
        $json = json_encode(value: $masked, flags: JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            return false;
        }

        return (bool) Storage::disk(name: $this->disk)->put(
            path: $this->filePath(id: $id),
            contents: $json,
        );
    }

    public function delete(string $id): bool
    {
        return Storage::disk(name: $this->disk)->delete(
            paths: $this->filePath(id: $id),
        );
    }

    private function filePath(string $id): string
    {
        $cleanId = preg_replace(pattern: '/[^a-zA-Z0-9_\-\/]/', replacement: '', subject: $id) ?? $id;

        return trim(string: $this->basePath, characters: '/').'/'.ltrim(string: $cleanId, characters: '/').'.json';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function maskSecrets(array $data): array
    {
        if (isset($data['headers']) && is_array(value: $data['headers'])) {
            foreach ($data['headers'] as $key => $val) {
                if (strcasecmp(string1: (string) $key, string2: 'authorization') === 0) {
                    $data['headers'][$key] = 'Bearer ********';
                }
            }
        }

        $authKeys = ['auth_payload', 'authPayload'];
        foreach ($authKeys as $authKey) {
            if (isset($data[$authKey]) && is_array(value: $data[$authKey])) {
                foreach ($data[$authKey] as $key => $val) {
                    if (in_array(needle: strtolower(string: (string) $key), haystack: ['token', 'password', 'secret', 'key', 'value'], strict: true)) {
                        $data[$authKey][$key] = '********';
                    }
                }
            }
        }

        if (isset($data['body']) && is_array(value: $data['body'])) {
            foreach ($data['body'] as $key => $val) {
                if (in_array(needle: strtolower(string: (string) $key), haystack: ['password', 'token', 'secret', 'api_key'], strict: true)) {
                    $data['body'][$key] = '********';
                }
            }
        }

        return $data;
    }
}
