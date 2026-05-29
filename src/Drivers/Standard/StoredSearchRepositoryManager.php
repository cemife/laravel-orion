<?php

namespace Orion\Drivers\Standard;

use InvalidArgumentException;
use Orion\Contracts\StoredSearchRepository;

class StoredSearchRepositoryManager implements StoredSearchRepository
{
    public function store(array $search): string
    {
        return $this->driver()->store($search);
    }

    public function find(string $id): ?array
    {
        return $this->driver()->find($id);
    }

    protected function driver(): StoredSearchRepository
    {
        $driver = config('orion.search_links.driver', 'filesystem');

        switch ($driver) {
            case 'filesystem':
                return app(FilesystemStoredSearchRepository::class);
            case 'database':
                return app(DatabaseStoredSearchRepository::class);
            case 'redis':
                return app(RedisStoredSearchRepository::class);
            default:
                throw new InvalidArgumentException("Unsupported Orion search link storage driver [{$driver}].");
        }
    }
}
