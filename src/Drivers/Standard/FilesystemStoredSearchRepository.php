<?php

namespace Orion\Drivers\Standard;

use Illuminate\Support\Str;
use Orion\Contracts\StoredSearchRepository;

class FilesystemStoredSearchRepository implements StoredSearchRepository
{
    public function store(array $search): string
    {
        $id = $this->makeId();
        $payload = json_encode([
            'expires_at' => $this->expiresAt(),
            'search' => $search,
        ]);

        if (!is_dir($this->path())) {
            mkdir($this->path(), 0777, true);
        }

        file_put_contents($this->file($id), $payload);

        return $id;
    }

    public function find(string $id): ?array
    {
        if (!$this->validId($id) || !is_file($this->file($id))) {
            return null;
        }

        $payload = json_decode((string) file_get_contents($this->file($id)), true);

        if (!is_array($payload) || !isset($payload['search']) || !is_array($payload['search'])) {
            return null;
        }

        if ($this->expired($payload['expires_at'] ?? null)) {
            @unlink($this->file($id));

            return null;
        }

        return $payload['search'];
    }

    protected function path(): string
    {
        return config('orion.search_links.filesystem.path', storage_path('framework/orion/search-links'));
    }

    protected function file(string $id): string
    {
        return rtrim($this->path(), '/\\').DIRECTORY_SEPARATOR.$id.'.json';
    }

    protected function makeId(): string
    {
        $length = max(1, (int) config('orion.search_links.id_length', 12));

        return config('orion.search_links.id_prefix', 'srch_').Str::random($length);
    }

    protected function expiresAt(): ?int
    {
        $ttl = config('orion.search_links.ttl', 86400);

        return $ttl === null ? null : time() + (int) $ttl;
    }

    protected function expired(?int $expiresAt): bool
    {
        return $expiresAt !== null && $expiresAt <= time();
    }

    protected function validId(string $id): bool
    {
        return preg_match('/^[A-Za-z0-9_-]+$/', $id) === 1;
    }
}
