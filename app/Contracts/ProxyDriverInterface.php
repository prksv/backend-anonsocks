<?php

namespace App\Contracts;

interface ProxyDriverInterface
{
    public function fromPriorityPool();

    public function getAllProxies(): array;

    public function sync(): void;

    public function formatProxy($proxy): array;
}
