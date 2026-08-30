<?php

namespace Sitewyn\Packages\Media\Support;

class RemoteUrlGuard
{
    /**
     * Blocked IPv4 ranges as [network, prefix length] pairs: unspecified and
     * this-network (0.0.0.0/8), RFC 1918 private space, carrier-grade NAT
     * (100.64.0.0/10), IPv4 loopback (127.0.0.0/8) and link-local (169.254.0.0/16,
     * which covers the cloud metadata service).
     *
     * @var array<int, array{0: string, 1: int}>
     */
    private const BLOCKED_IPV4_RANGES = [
        ['0.0.0.0', 8],
        ['10.0.0.0', 8],
        ['100.64.0.0', 10],
        ['127.0.0.0', 8],
        ['169.254.0.0', 16],
        ['172.16.0.0', 12],
        ['192.168.0.0', 16],
    ];

    /**
     * Blocked IPv6 ranges as [network, prefix length] pairs: loopback (::1/128),
     * unique local addresses (fc00::/7) and link-local addresses (fe80::/10).
     *
     * @var array<int, array{0: string, 1: int}>
     */
    private const BLOCKED_IPV6_RANGES = [
        ['::1', 128],
        ['fc00::', 7],
        ['fe80::', 10],
    ];

    public function __construct(private readonly DnsResolver $resolver) {}

    /**
     * Ensure the URL may be fetched from the server: only http(s) and a host
     * that does not resolve into a forbidden network range.
     *
     * @throws UnsafeUrlException
     */
    public function assertSafe(string $url): void
    {
        $parts = parse_url($url);
        $scheme = is_array($parts) && isset($parts['scheme']) && is_string($parts['scheme']) ? strtolower($parts['scheme']) : '';
        $host = is_array($parts) && isset($parts['host']) && is_string($parts['host']) ? $parts['host'] : '';

        if ($scheme === '' || $host === '') {
            throw new UnsafeUrlException('The URL does not contain a scheme and host.');
        }

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new UnsafeUrlException("The URL scheme [{$scheme}] is not allowed.");
        }

        $this->assertHostSafe($host);
    }

    /**
     * Ensure a host (as used in a URL or a redirect hop) is fetchable.
     *
     * @throws UnsafeUrlException
     */
    public function assertHostSafe(string $host): void
    {
        $host = strtolower(trim($host));

        if ($host === '') {
            throw new UnsafeUrlException('The URL host is empty.');
        }

        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, 1, -1);
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            $this->assertIpSafe($host);

            return;
        }

        $addresses = $this->resolver->resolve($host);

        if ($addresses === []) {
            throw new UnsafeUrlException("The URL host [{$host}] could not be resolved.");
        }

        foreach ($addresses as $address) {
            $this->assertIpSafe($address);
        }
    }

    /**
     * @throws UnsafeUrlException
     */
    private function assertIpSafe(string $address): void
    {
        $binary = @inet_pton($this->normalizeIpv4Mapped($address));

        if ($binary === false) {
            throw new UnsafeUrlException("The URL host resolved to an unparseable address [{$address}].");
        }

        $ranges = strlen($binary) === 4 ? self::BLOCKED_IPV4_RANGES : self::BLOCKED_IPV6_RANGES;

        foreach ($ranges as [$network, $prefix]) {
            if ($this->addressInCidr($binary, $network, $prefix)) {
                throw new UnsafeUrlException("The URL host resolved to a forbidden address [{$address}].");
            }
        }
    }

    /**
     * Rewrite IPv4-mapped IPv6 addresses (::ffff:a.b.c.d) to plain IPv4 so they
     * are checked against the IPv4 blocklist as well.
     */
    private function normalizeIpv4Mapped(string $address): string
    {
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return $address;
        }

        $binary = @inet_pton($address);

        if ($binary !== false
            && strlen($binary) === 16
            && str_starts_with($binary, "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff")) {
            $ipv4 = inet_ntop(substr($binary, 12));

            if (is_string($ipv4)) {
                return $ipv4;
            }
        }

        return $address;
    }

    /**
     * Compare the subnet bits of a packed IP address against a CIDR network.
     *
     * @param  string  $binaryAddress  4 or 16 byte packed address from inet_pton()
     */
    private function addressInCidr(string $binaryAddress, string $network, int $prefix): bool
    {
        $binaryNetwork = @inet_pton($network);

        if ($binaryNetwork === false || strlen($binaryNetwork) !== strlen($binaryAddress)) {
            return false;
        }

        $fullBytes = intdiv($prefix, 8);
        $partialBits = $prefix % 8;

        if (strncmp($binaryAddress, $binaryNetwork, $fullBytes) !== 0) {
            return false;
        }

        if ($partialBits === 0) {
            return true;
        }

        $mask = chr((0xFF << (8 - $partialBits)) & 0xFF);

        return ($binaryAddress[$fullBytes] & $mask) === ($binaryNetwork[$fullBytes] & $mask);
    }
}
