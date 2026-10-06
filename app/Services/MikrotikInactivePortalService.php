<?php

namespace App\Services;

use App\Models\MikrotikRouter;
use App\Models\Organization;
use RuntimeException;
use Throwable;

class MikrotikInactivePortalService
{
    public const INACTIVE_ADDRESS_LIST = 'isp-codex-inactive';

    public const PORTAL_ADDRESS_LIST = 'isp-codex-please-call';

    public const PUBLIC_DNS_ADDRESS_LIST = 'isp-codex-public-dns';

    private const RULE_PREFIX = 'ISP Codex Please Call';

    private const PORTAL_NETWORK = '162.4.6.0/23';

    private const PUBLIC_DNS_SERVERS = ['8.8.8.8', '8.8.4.4', '1.1.1.1'];

    public function portalUrl(): string
    {
        return route('service-inactive', [], true);
    }

    public function hasConfiguredNumbers(): bool
    {
        return (Organization::defaultOrganization()?->pleaseCallNumbers() ?? []) !== [];
    }

    /**
     * @return array{configured: int, skipped: int, failed: int, messages: array<int, string>}
     */
    public function configureAll(): array
    {
        $summary = ['configured' => 0, 'skipped' => 0, 'failed' => 0, 'messages' => []];

        foreach (MikrotikRouter::query()->where('status', 'active')->orderBy('id')->get() as $router) {
            if ($router->pushDisabled()) {
                $summary['skipped']++;
                $summary['messages'][] = "{$router->name}: skipped (read-only or REST-import router).";

                continue;
            }

            try {
                $this->configure($router);
                $summary['configured']++;
            } catch (Throwable $exception) {
                $summary['failed']++;
                $summary['messages'][] = "{$router->name}: {$exception->getMessage()}";
            }
        }

        return $summary;
    }

    /** @return array{profile: string, address_list: string, portal_url: string, proxy_port: int, dns_server: string, reconnected: int} */
    public function configure(MikrotikRouter $router): array
    {
        if ($router->pushDisabled()) {
            throw new RuntimeException('This router is read-only or uses the REST import transport, so configuration cannot be pushed.');
        }

        $client = new RouterOsClient;

        try {
            $client->connect(
                $router->ip_address,
                $router->api_port,
                $router->username,
                $router->apiPassword(),
                10
            );

            return $this->configureWithClient($client, $router);
        } finally {
            $client->close();
        }
    }

    /** @return array{profile: string, address_list: string, portal_url: string, proxy_port: int, dns_server: string, reconnected: int} */
    public function configureWithClient(RouterOsClient $client, MikrotikRouter $router): array
    {
        if (! $this->hasConfiguredNumbers()) {
            throw new RuntimeException('Add at least one Please Call number to the default Organization before applying the redirect.');
        }

        $profile = trim((string) $router->inactive_pppoe_profile);
        if ($profile === '') {
            throw new RuntimeException('The inactive PPPoE profile name is empty.');
        }

        $url = $this->portalUrl();
        $urlParts = parse_url($url);
        $scheme = mb_strtolower((string) ($urlParts['scheme'] ?? ''));
        $host = trim((string) ($urlParts['host'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new RuntimeException("The generated Please Call URL is invalid: {$url}");
        }

        if (in_array(mb_strtolower($host), ['localhost', '127.0.0.1', '::1'], true)) {
            throw new RuntimeException('APP_URL must use the public portal domain or server IP; localhost cannot be opened by PPPoE customers.');
        }

        $dnsServer = $this->ensureInactiveProfileNetworkPolicy($client, $profile);
        $dns = $client->command('/ip/dns/print', [
            '.proplist' => 'allow-remote-requests',
        ])[0] ?? [];

        if (! $this->routerBoolean($dns['allow-remote-requests'] ?? false)) {
            $client->command('/ip/dns/set', [
                'allow-remote-requests' => 'yes',
            ]);
        }

        $proxyRules = $client->command('/ip/proxy/access/print', ['.proplist' => '.id,comment']);
        $ownedProxyRules = array_values(array_filter($proxyRules, fn (array $row): bool => $this->isTagged($row)));
        $unmanagedProxyRules = array_values(array_filter($proxyRules, fn (array $row): bool => ! $this->isTagged($row)));
        $proxy = $client->command('/ip/proxy/print', [
            '.proplist' => 'enabled,port',
        ])[0] ?? [];
        $proxyEnabled = $this->routerBoolean($proxy['enabled'] ?? false);

        if (($proxyEnabled && $ownedProxyRules === []) || $unmanagedProxyRules !== []) {
            throw new RuntimeException('RouterOS Web Proxy already has another configuration. The app left it unchanged to avoid breaking existing proxy rules.');
        }

        $proxyPort = $this->proxyPort($proxy, $router);

        $this->removeTaggedRows($client, '/ip/proxy/access');
        $this->removeTaggedRows($client, '/ip/firewall/nat');
        $this->removeTaggedRows($client, '/ip/firewall/filter');
        $this->removeTaggedRows($client, '/ip/firewall/address-list');

        $client->command('/ip/firewall/address-list/add', [
            'list' => self::PORTAL_ADDRESS_LIST,
            'address' => $host,
            'comment' => self::RULE_PREFIX.' destination',
        ]);
        foreach (self::PUBLIC_DNS_SERVERS as $dnsAddress) {
            $client->command('/ip/firewall/address-list/add', [
                'list' => self::PUBLIC_DNS_ADDRESS_LIST,
                'address' => $dnsAddress,
                'comment' => self::RULE_PREFIX.' public DNS',
            ]);
        }

        // Proxy access rules are first-match. The managed list is empty here,
        // so append the portal exception first and the catch-all redirect last.
        // `place-before=0` fails on RouterOS when the access list has no row 0.
        $client->command('/ip/proxy/access/add', [
            'dst-host' => $host,
            'action' => 'allow',
            'comment' => self::RULE_PREFIX.' allow portal host',
        ]);
        $client->command('/ip/proxy/access/add', [
            'action' => 'redirect',
            'action-data' => $url,
            'comment' => self::RULE_PREFIX.' redirect',
        ]);

        // RouterOS keeps the existing proxy process alive when its bind address
        // or cache mode changes. Restart it so the inactive profile gateway is
        // listening before users are reconnected below.
        $client->command('/ip/proxy/set', [
            'enabled' => 'no',
        ]);
        $client->command('/ip/proxy/set', [
            'enabled' => 'yes',
            'port' => (string) $proxyPort,
            'src-address' => $dnsServer,
            'cache-on-disk' => 'no',
            // On the live TILE router a small cache left the listener unable to
            // serve redirected PPPoE connections. RouterOS' supported RAM-cache
            // mode keeps the proxy running; these rules only return redirects.
            'max-cache-size' => 'unlimited',
        ]);

        // Add restrictive rules first, then insert exceptions above them.
        $client->command('/ip/firewall/filter/add', [
            'chain' => 'input',
            'src-address-list' => self::INACTIVE_ADDRESS_LIST,
            'protocol' => 'tcp',
            'dst-port' => (string) $proxyPort,
            'action' => 'accept',
            'comment' => self::RULE_PREFIX.' allow transparent proxy',
            'place-before' => '0',
        ]);
        $client->command('/ip/firewall/filter/add', [
            'chain' => 'input',
            'src-address-list' => self::INACTIVE_ADDRESS_LIST,
            'protocol' => 'tcp',
            'dst-port' => '53',
            'action' => 'accept',
            'comment' => self::RULE_PREFIX.' allow router DNS TCP',
            'place-before' => '0',
        ]);
        $client->command('/ip/firewall/filter/add', [
            'chain' => 'input',
            'src-address-list' => self::INACTIVE_ADDRESS_LIST,
            'protocol' => 'udp',
            'dst-port' => '53',
            'action' => 'accept',
            'comment' => self::RULE_PREFIX.' allow router DNS UDP',
            'place-before' => '0',
        ]);
        $client->command('/ip/firewall/filter/add', [
            'chain' => 'input',
            'protocol' => 'tcp',
            'dst-port' => (string) $proxyPort,
            'action' => 'drop',
            'comment' => self::RULE_PREFIX.' block other proxy clients',
            'place-before' => '0',
        ]);
        $client->command('/ip/firewall/filter/add', [
            'chain' => 'forward',
            'src-address-list' => self::INACTIVE_ADDRESS_LIST,
            // The portal server is inside this routed network. Permit the full
            // network before the inactive catch-all reject so ICMP diagnostics
            // and both HTTP/HTTPS can use the source-NAT rule below.
            'dst-address' => self::PORTAL_NETWORK,
            'action' => 'accept',
            'comment' => self::RULE_PREFIX.' allow portal',
            'place-before' => '0',
        ]);
        foreach (['tcp', 'udp'] as $protocol) {
            $client->command('/ip/firewall/filter/add', [
                'chain' => 'forward',
                'src-address-list' => self::INACTIVE_ADDRESS_LIST,
                'dst-address-list' => self::PUBLIC_DNS_ADDRESS_LIST,
                'protocol' => $protocol,
                'dst-port' => '53',
                'action' => 'accept',
                'comment' => self::RULE_PREFIX.' allow public DNS '.strtoupper($protocol),
                'place-before' => '0',
            ]);
        }
        $client->command('/ip/firewall/filter/add', [
            'chain' => 'forward',
            'src-address-list' => self::INACTIVE_ADDRESS_LIST,
            'action' => 'reject',
            'reject-with' => 'icmp-network-unreachable',
            'comment' => self::RULE_PREFIX.' block other traffic',
            'place-before' => '0',
        ]);
        $client->command('/ip/firewall/nat/add', [
            'chain' => 'dstnat',
            'src-address-list' => self::INACTIVE_ADDRESS_LIST,
            'protocol' => 'tcp',
            'dst-port' => '80',
            // Redirect to the router address assigned as the inactive PPP
            // profile gateway. This is also the address the proxy binds above.
            'action' => 'redirect',
            'to-addresses' => $dnsServer,
            'to-ports' => (string) $proxyPort,
            'comment' => self::RULE_PREFIX.' HTTP redirect',
            'place-before' => '0',
        ]);
        $client->command('/ip/firewall/nat/add', [
            'chain' => 'srcnat',
            'src-address-list' => self::INACTIVE_ADDRESS_LIST,
            'dst-address' => self::PORTAL_NETWORK,
            'action' => 'src-nat',
            'to-addresses' => $router->ip_address,
            'comment' => self::RULE_PREFIX.' portal source NAT',
            'place-before' => '0',
        ]);
        $client->command('/ip/firewall/nat/add', [
            'chain' => 'srcnat',
            'src-address-list' => self::INACTIVE_ADDRESS_LIST,
            'dst-address-list' => self::PUBLIC_DNS_ADDRESS_LIST,
            'action' => 'src-nat',
            'to-addresses' => $router->ip_address,
            'comment' => self::RULE_PREFIX.' public DNS source NAT',
            'place-before' => '0',
        ]);

        // `/ppp/active` does not expose the profile on RouterOS 7. Match active
        // sessions to inactive secrets (and the profile's dynamic address list)
        // so only inactive users reconnect and receive the MikroTik DNS address.
        $inactiveNames = collect($client->command('/ppp/secret/print', [
            '?profile' => $profile,
            '.proplist' => 'name,profile',
        ]))
            ->filter(fn (array $secret): bool => trim((string) ($secret['profile'] ?? '')) === $profile)
            ->mapWithKeys(fn (array $secret): array => [trim((string) ($secret['name'] ?? '')) => true])
            ->forget('')
            ->all();
        $inactiveAddresses = collect($client->command('/ip/firewall/address-list/print', [
            '?list' => self::INACTIVE_ADDRESS_LIST,
            '.proplist' => 'list,address',
        ]))
            ->filter(fn (array $row): bool => trim((string) ($row['list'] ?? '')) === self::INACTIVE_ADDRESS_LIST)
            ->mapWithKeys(fn (array $row): array => [trim((string) ($row['address'] ?? '')) => true])
            ->forget('')
            ->all();
        $activeSessions = $client->command('/ppp/active/print', [
            '.proplist' => '.id,name,address',
        ]);
        $reconnected = 0;
        foreach ($activeSessions as $session) {
            $name = trim((string) ($session['name'] ?? ''));
            $address = trim((string) ($session['address'] ?? ''));
            if (empty($session['.id']) || (! isset($inactiveNames[$name]) && ! isset($inactiveAddresses[$address]))) {
                continue;
            }

            $client->command('/ppp/active/remove', ['.id' => $session['.id']]);
            $reconnected++;
        }

        return [
            'profile' => $profile,
            'address_list' => self::INACTIVE_ADDRESS_LIST,
            'portal_url' => $url,
            'proxy_port' => $proxyPort,
            'dns_server' => $dnsServer,
            'reconnected' => $reconnected,
        ];
    }

    private function ensureInactiveProfileNetworkPolicy(RouterOsClient $client, string $profile): string
    {
        $profiles = $client->command('/ppp/profile/print', [
            '?name' => $profile,
            '.proplist' => '.id,name,address-list,use-ipv6,local-address,dns-server',
        ]);

        if ($profiles === []) {
            $client->command('/ppp/profile/add', [
                'name' => $profile,
                'address-list' => self::INACTIVE_ADDRESS_LIST,
                'use-ipv6' => 'no',
            ]);

            throw new RuntimeException("Inactive PPP profile {$profile} was created, but it needs a router IPv4 local-address before the Please Call redirect can use MikroTik DNS.");
        }

        if (count($profiles) !== 1 || trim((string) ($profiles[0]['name'] ?? '')) !== $profile) {
            throw new RuntimeException("RouterOS returned a mismatched PPP profile while configuring {$profile}.");
        }

        $dnsServer = trim((string) ($profiles[0]['local-address'] ?? ''));
        if (filter_var($dnsServer, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            throw new RuntimeException("Inactive PPP profile {$profile} must have a router IPv4 local-address before the Please Call redirect can use MikroTik DNS.");
        }

        $changes = [];
        if (trim((string) ($profiles[0]['address-list'] ?? '')) !== self::INACTIVE_ADDRESS_LIST) {
            $changes['address-list'] = self::INACTIVE_ADDRESS_LIST;
        }
        if (mb_strtolower(trim((string) ($profiles[0]['use-ipv6'] ?? ''))) !== 'no') {
            $changes['use-ipv6'] = 'no';
        }
        if (trim((string) ($profiles[0]['dns-server'] ?? '')) !== $dnsServer) {
            $changes['dns-server'] = $dnsServer;
        }

        if ($changes !== []) {
            $client->command('/ppp/profile/set', [
                '.id' => $profiles[0]['.id'],
                ...$changes,
            ]);
        }

        return $dnsServer;
    }

    /** @return array<int, array<string, string>> */
    private function taggedRows(RouterOsClient $client, string $printCommand): array
    {
        return array_values(array_filter(
            $client->command($printCommand, ['.proplist' => '.id,comment']),
            fn (array $row): bool => $this->isTagged($row)
        ));
    }

    /** @param array<string, string> $row */
    private function isTagged(array $row): bool
    {
        return str_starts_with(trim((string) ($row['comment'] ?? '')), self::RULE_PREFIX);
    }

    private function removeTaggedRows(RouterOsClient $client, string $menu): void
    {
        foreach ($this->taggedRows($client, $menu.'/print') as $row) {
            if (! empty($row['.id'])) {
                $client->command($menu.'/remove', ['.id' => $row['.id']]);
            }
        }
    }

    /** @param array<string, string> $proxy */
    private function proxyPort(array $proxy, MikrotikRouter $router): int
    {
        $current = filter_var($proxy['port'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535],
        ]);

        if ($current && (int) $current !== (int) $router->api_port) {
            return (int) $current;
        }

        return (int) $router->api_port === 8080 ? 3128 : 8080;
    }

    private function routerBoolean(mixed $value): bool
    {
        return in_array(mb_strtolower(trim((string) $value)), ['true', 'yes', '1', 'on'], true);
    }
}
