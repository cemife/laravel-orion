<?php

namespace Orion\Drivers\Standard;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Str;
use Orion\Contracts\StoredSearchRepository;

class RedisStoredSearchRepository implements StoredSearchRepository
{
    /**
     * @var CacheFactory
     */
    protected $cache;

    public function __construct(CacheFactory $cache)
    {
        $this->cache = $cache;
    }

    public function store(array $search): string
    {
        $id = $this->makeId();
        $ttl = config('orion.search_links.ttl', 86400);

        if ($ttl === null) {
            $this->cache()->forever($this->key($id), $search);
        } else {
            $this->cache()->put($this->key($id), $search, (int) $ttl);
        }

        return $id;
    }

    public function find(string $id): ?array
    {
        if (!$this->validId($id)) {
            return null;
        }

        $search = $this->cache()->get($this->key($id));

        return is_array($search) ? $search : null;
    }

    protected function cache(): CacheRepository
    {
        return $this->cache->store(config('orion.search_links.redis.cache_store', 'redis'));
    }

    protected function makeId(): string
    {
        $length = max(1, (int) config('orion.search_links.id_length', 12));

        return config('orion.search_links.id_prefix', 'srch_').Str::random($length);
    }

    protected function key(string $id): string
    {
        return config('orion.search_links.redis.key_prefix', 'orion:search-links:').$id;
    }

    protected function validId(string $id): bool
    {
        return preg_match('/^[A-Za-z0-9_-]+$/', $id) === 1;
    }
}
