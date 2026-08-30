<?php

namespace Sitewyn\Packages\Media\Support;

class PhpDnsResolver implements DnsResolver
{
    /**
     * Resolve a hostname to its IPv4 addresses, preferring DNS A records and
     * falling back to gethostbyname.
     *
     * @return list<string>
     */
    public function resolve(string $host): array
    {
        $addresses = [];

        foreach ((array) @dns_get_record($host, DNS_A) as $record) {
            if (is_array($record)
                && isset($record['ip'])
                && is_string($record['ip'])
                && filter_var($record['ip'], FILTER_VALIDATE_IP) !== false) {
                $addresses[] = $record['ip'];
            }
        }

        if ($addresses === []) {
            $fallback = @gethostbyname($host);

            if (is_string($fallback) && $fallback !== $host && filter_var($fallback, FILTER_VALIDATE_IP) !== false) {
                $addresses[] = $fallback;
            }
        }

        return $addresses;
    }
}
