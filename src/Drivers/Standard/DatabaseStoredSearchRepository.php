<?php

namespace Orion\Drivers\Standard;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Orion\Contracts\StoredSearchRepository;

class DatabaseStoredSearchRepository implements StoredSearchRepository
{
    public function store(array $search): string
    {
        $id = $this->makeId();
        $now = date('Y-m-d H:i:s');

        $this->table()->updateOrInsert(
            ['id' => $id],
            [
                'payload' => json_encode($search),
                'expires_at' => $this->expiresAt(),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        return $id;
    }

    public function find(string $id): ?array
    {
        if (!$this->validId($id)) {
            return null;
        }

        $record = $this->table()->where('id', $id)->first();

        if (!$record) {
            return null;
        }

        if ($this->expired($record->expires_at)) {
            $this->table()->where('id', $id)->delete();

            return null;
        }

        $search = json_decode($record->payload, true);

        return is_array($search) ? $search : null;
    }

    protected function table()
    {
        return DB::connection(config('orion.search_links.database.connection'))
            ->table(config('orion.search_links.database.table', 'orion_search_links'));
    }

    protected function makeId(): string
    {
        $length = max(1, (int) config('orion.search_links.id_length', 12));

        return config('orion.search_links.id_prefix', 'srch_').Str::random($length);
    }

    protected function expiresAt(): ?string
    {
        $ttl = config('orion.search_links.ttl', 86400);

        return $ttl === null ? null : date('Y-m-d H:i:s', time() + (int) $ttl);
    }

    protected function expired(?string $expiresAt): bool
    {
        return $expiresAt !== null && strtotime($expiresAt) <= time();
    }

    protected function validId(string $id): bool
    {
        return preg_match('/^[A-Za-z0-9_-]+$/', $id) === 1;
    }
}
