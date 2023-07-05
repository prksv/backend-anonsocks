<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface ProxyDriverInterface
{
    public function fromPriorityPool();

    public function getProxies(string $country_code, int $count): Collection;

    public function sync(): void;

    public function formatProxy($proxy): array;
}
