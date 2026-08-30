<?php

namespace App\Http\Controllers;

use App\Models\MikrotikRouter;
use App\Services\MikrotikImportService;
use Illuminate\Http\Request;

/**
 * Troubleshoot → Router Live Data. Pulls a fixed set of read-only
 * `.../print` results from every active MikroTik router in one shot, plus an
 * optional operator-supplied print path, and hands them to the view grouped
 * by router. Nothing here writes to a router.
 */
class RouterLiveDataController extends Controller
{
    /**
     * label => RouterOS print path. Kept deliberately short so the page stays
     * responsive across every active router; anything else goes through the
     * command box.
     */
    private const SECTIONS = [
        'System resource' => '/system/resource/print',
        'Identity' => '/system/identity/print',
        'RouterBOARD' => '/system/routerboard/print',
        'Interfaces' => '/interface/print',
        'PPP active' => '/ppp/active/print',
        'IP addresses' => '/ip/address/print',
        'IP pools' => '/ip/pool/print',
        'ARP table' => '/ip/arp/print',
        'DHCP leases' => '/ip/dhcp-server/lease/print',
        'Routes' => '/ip/route/print',
        'Firewall filter' => '/ip/firewall/filter/print',
        'Firewall NAT' => '/ip/firewall/nat/print',
        'Simple queues' => '/queue/simple/print',
        'Recent log' => '/log/print',
    ];

    /** Rows rendered per section — protects the browser from a huge table. */
    public const ROW_CAP = 500;

    public function __construct(private readonly MikrotikImportService $importService) {}

    public function index(Request $request)
    {
        $routers = MikrotikRouter::query()->where('status', 'active')->orderBy('name')->get();

        $command = trim((string) $request->query('command', ''));
        $commandError = null;
        if ($command !== '' && ! $this->isReadOnlyPrintPath($command)) {
            $commandError = 'Enter a read-only path that ends in /print, e.g. /interface/ethernet/print.';
            $command = '';
        }

        $commands = array_values(self::SECTIONS);
        if ($command !== '') {
            $commands[] = $command;
        }

        $results = $routers->map(fn (MikrotikRouter $router) => [
            'router' => $router,
            'sections' => $this->importService->liveSnapshot($router, $commands),
        ])->values();

        return view('troubleshoot.router_data', [
            'routers' => $routers,
            'sectionMap' => self::SECTIONS,
            'results' => $results,
            'command' => $command,
            'commandError' => $commandError,
            'rowCap' => self::ROW_CAP,
            'fetchedAt' => now(),
        ]);
    }

    private function isReadOnlyPrintPath(string $path): bool
    {
        return (bool) preg_match('#^/[a-z0-9][a-z0-9/\-]*/print$#i', $path);
    }
}
