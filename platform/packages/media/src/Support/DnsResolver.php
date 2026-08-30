<?php

namespace Sitewyn\Packages\Media\Support;

interface DnsResolver
{
    /**
     * Resolve a hostname to the list of IP addresses it points to.
     *
     * An empty list signals that the host could not be resolved.
     *
     * @return list<string>
     */
    public function resolve(string $host): array;
}
