<?php

namespace Tests\Support;

use Sitewyn\Packages\Media\Support\DnsResolver;

class FakeDnsResolver implements DnsResolver
{
    /**
     * @param  array<string, list<string>>  $map
     */
    public function __construct(private readonly array $map = []) {}

    public function resolve(string $host): array
    {
        return $this->map[$host] ?? [];
    }
}
