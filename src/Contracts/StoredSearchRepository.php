<?php

namespace Orion\Contracts;

interface StoredSearchRepository
{
    public function store(array $search): string;

    public function find(string $id): ?array;
}
