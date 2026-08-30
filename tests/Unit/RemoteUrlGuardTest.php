<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sitewyn\Packages\Media\Support\RemoteUrlGuard;
use Sitewyn\Packages\Media\Support\UnsafeUrlException;
use Tests\Support\FakeDnsResolver;

class RemoteUrlGuardTest extends TestCase
{
    public function test_blocks_private_and_reserved_ipv4_targets(): void
    {
        $guard = new RemoteUrlGuard(new FakeDnsResolver);

        foreach ([
            '127.0.0.1',
            '10.0.0.5',
            '172.16.0.9',
            '172.31.255.255',
            '192.168.1.1',
            '169.254.169.254',
            '0.0.0.0',
            '100.64.1.1',
        ] as $ip) {
            $this->assertUnsafe($guard, "http://{$ip}/x");
            $this->assertUnsafe($guard, "https://{$ip}:8080/x");
        }
    }

    public function test_blocks_ipv6_loopback_and_local_ranges(): void
    {
        $guard = new RemoteUrlGuard(new FakeDnsResolver);

        $this->assertUnsafe($guard, 'http://[::1]/x');
        $this->assertUnsafe($guard, 'http://[fc00::1]/x');
        $this->assertUnsafe($guard, 'http://[fe80::1]/x');
        $this->assertUnsafe($guard, 'http://[::ffff:127.0.0.1]/x');
        $this->assertUnsafe($guard, 'http://[::ffff:10.0.0.5]/x');
    }

    public function test_allows_public_ip_targets(): void
    {
        $guard = new RemoteUrlGuard(new FakeDnsResolver);

        $this->assertAllowed($guard, 'https://93.184.216.34/asset.png');
        $this->assertAllowed($guard, 'http://8.8.8.8/');
        $this->assertAllowed($guard, 'https://[2606:4700::1110]/asset.png');
    }

    public function test_blocks_hostnames_resolving_into_forbidden_ranges(): void
    {
        $guard = new RemoteUrlGuard(new FakeDnsResolver([
            'internal.example' => ['10.0.0.5'],
            'metadata.example' => ['169.254.169.254'],
            'mixed.example' => ['93.184.216.34', '192.168.0.10'],
        ]));

        $this->assertUnsafe($guard, 'http://internal.example/x');
        $this->assertUnsafe($guard, 'http://metadata.example/latest/meta-data');
        $this->assertUnsafe($guard, 'http://mixed.example/x');
    }

    public function test_allows_hostnames_resolving_to_public_addresses(): void
    {
        $guard = new RemoteUrlGuard(new FakeDnsResolver([
            'example.com' => ['93.184.216.34', '203.0.113.7'],
        ]));

        $this->assertAllowed($guard, 'https://example.com/asset.png');
    }

    public function test_blocks_hostname_when_dns_resolution_fails(): void
    {
        $guard = new RemoteUrlGuard(new FakeDnsResolver);

        $this->assertUnsafe($guard, 'http://unknown.example/asset.png');
    }

    public function test_blocks_non_http_schemes(): void
    {
        $guard = new RemoteUrlGuard(new FakeDnsResolver);

        $this->assertUnsafe($guard, 'file://169.254.169.254/latest/meta-data');
        $this->assertUnsafe($guard, 'file:///etc/passwd');
        $this->assertUnsafe($guard, 'ftp://93.184.216.34/asset.png');
        $this->assertUnsafe($guard, 'gopher://93.184.216.34:70/');
    }

    public function test_blocks_urls_without_scheme_or_host(): void
    {
        $guard = new RemoteUrlGuard(new FakeDnsResolver);

        $this->assertUnsafe($guard, 'not-a-url');
        $this->assertUnsafe($guard, 'http:///no-host');
    }

    public function test_assert_host_safe_reuses_the_same_rules_for_redirect_hops(): void
    {
        $guard = new RemoteUrlGuard(new FakeDnsResolver);

        try {
            $guard->assertHostSafe('127.0.0.1');
            $this->fail('Expected the loopback host to be blocked.');
        } catch (UnsafeUrlException) {
            $this->addToAssertionCount(1);
        }

        try {
            $guard->assertHostSafe('[::1]');
            $this->fail('Expected the IPv6 loopback host to be blocked.');
        } catch (UnsafeUrlException) {
            $this->addToAssertionCount(1);
        }

        try {
            $guard->assertHostSafe('internal.example');
            $this->fail('Expected the unresolvable host to be blocked.');
        } catch (UnsafeUrlException) {
            $this->addToAssertionCount(1);
        }

        $guard->assertHostSafe('8.8.8.8');
        $this->addToAssertionCount(1);
    }

    private function assertUnsafe(RemoteUrlGuard $guard, string $url): void
    {
        try {
            $guard->assertSafe($url);
        } catch (UnsafeUrlException) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail("Expected [{$url}] to be blocked.");
    }

    private function assertAllowed(RemoteUrlGuard $guard, string $url): void
    {
        try {
            $guard->assertSafe($url);
        } catch (UnsafeUrlException $exception) {
            $this->fail("Expected [{$url}] to be allowed: {$exception->getMessage()}");
        }

        $this->addToAssertionCount(1);
    }
}
