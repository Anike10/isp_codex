<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\NetworkMapFeature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NetworkMapController extends Controller
{
    private const NODE_TYPES = ['router', 'switch', 'olt', 'splitter', 'tj_box', 'onu'];

    private const LINK_TYPES = ['fiber_cable'];

    public function show(Request $request): View
    {
        return view('network_map.index', [
            'title' => 'FTTX Network Map',
            'initialCustomerId' => $request->integer('customer_id') ?: null,
        ]);
    }

    public function partyLocations(Request $request): View
    {
        return view('network_map.party_locations', [
            'title' => 'Party Location Manager',
            'initialCustomerId' => $request->integer('customer_id') ?: null,
        ]);
    }

    public function index(): JsonResponse
    {
        $features = NetworkMapFeature::query()
            ->latest('updated_at')
            ->get()
            ->map(fn (NetworkMapFeature $feature) => $feature->toGeoJsonFeature())
            ->values();

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    public function customers(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $features = Customer::query()
            ->with('importedSecret')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('connection_id', 'like', "%{$search}%")
                        ->orWhere('mikrotik_username', 'like', "%{$search}%")
                        ->orWhereHas('importedSecret', fn ($query) => $query->where('router_comment', 'like', "%{$search}%"))
                        ->orWhere('address', 'like', "%{$search}%");

                    if (ctype_digit($search)) {
                        $query->orWhere('id', (int) $search);
                    }
                });
            })
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'phone',
                'connection_id',
                'mikrotik_username',
                'address',
                'status',
                'map_latitude',
                'map_longitude',
            ])
            ->map(fn (Customer $customer) => $this->customerToFeature($customer))
            ->values();

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    public function updateCustomerLocation(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'map_latitude' => ['required', 'numeric', 'between:-90,90'],
            'map_longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $customer->update([
            'map_latitude' => (float) $validated['map_latitude'],
            'map_longitude' => (float) $validated['map_longitude'],
        ]);

        return response()->json($this->customerToFeature($customer->fresh()));
    }

    public function clearCustomerLocation(Customer $customer): JsonResponse
    {
        $customer->update([
            'map_latitude' => null,
            'map_longitude' => null,
        ]);

        return response()->json($this->customerToFeature($customer->fresh()));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['FeatureCollection'])],
            'features' => ['present', 'array'],
            'features.*.type' => ['required', Rule::in(['Feature'])],
            'features.*.id' => ['nullable', 'string', 'max:80'],
            'features.*.geometry' => ['required', 'array'],
            'features.*.geometry.type' => ['required', Rule::in(['Point', 'LineString'])],
            'features.*.geometry.coordinates' => ['required', 'array'],
            'features.*.properties' => ['required', 'array'],
            'features.*.properties.component_type' => ['required', 'string'],
            'features.*.properties.feature_type' => ['nullable', Rule::in(['node', 'link'])],
        ]);
        $features = $request->input('features', []);

        $saved = DB::transaction(function () use ($features, $request) {
            $incomingIds = collect($features)
                ->map(fn (array $feature) => (string) ($feature['id'] ?? Arr::get($feature, 'properties.id', '')))
                ->filter()
                ->values();

            if ($incomingIds->isNotEmpty()) {
                NetworkMapFeature::query()
                    ->whereNotIn('feature_uuid', $incomingIds)
                    ->delete();
            } else {
                NetworkMapFeature::query()->delete();
            }

            return collect($features)->map(function (array $feature) use ($request) {
                $geometry = $feature['geometry'];
                $componentType = Str::of(Arr::get($feature, 'properties.component_type'))->lower()->snake()->toString();
                $featureType = $geometry['type'] === 'Point' ? 'node' : 'link';

                abort_unless($this->isAllowedComponent($featureType, $componentType), 422, 'Invalid network component type.');
                $this->assertValidCoordinates($geometry);

                $properties = $this->normalizeProperties($feature['properties'], $featureType, $componentType);
                $featureUuid = (string) ($feature['id'] ?? Arr::get($properties, 'id') ?? Str::uuid());
                $point = $geometry['type'] === 'Point' ? $geometry['coordinates'] : null;
                $name = $this->displayName($properties, $componentType);

                $model = NetworkMapFeature::updateOrCreate(
                    ['feature_uuid' => $featureUuid],
                    [
                        'entry_by' => $request->user()?->id,
                        'feature_type' => $featureType,
                        'component_type' => $componentType,
                        'name' => $name,
                        'properties' => array_merge($properties, [
                            'id' => $featureUuid,
                            'feature_type' => $featureType,
                            'component_type' => $componentType,
                        ]),
                        'geometry' => $geometry,
                        'longitude' => $point ? round((float) $point[0], 8) : null,
                        'latitude' => $point ? round((float) $point[1], 8) : null,
                        'length_meters' => $featureType === 'link'
                            ? round((float) Arr::get($properties, 'length_meters', 0), 2)
                            : null,
                    ],
                );

                return $model->toGeoJsonFeature();
            })->values();
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $saved,
        ]);
    }

    public function uploadPhotos(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'photos' => ['required', 'array', 'max:12'],
            'photos.*' => ['required', 'image', 'max:5120'],
        ]);

        $directory = public_path('network-map-photos/'.now()->format('Y/m'));

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $photos = collect($validated['photos'])->map(function ($photo) use ($directory) {
            $extension = strtolower($photo->getClientOriginalExtension() ?: $photo->extension() ?: 'jpg');
            $filename = Str::uuid().'.'.$extension;
            $photo->move($directory, $filename);
            $relativePath = 'network-map-photos/'.now()->format('Y/m').'/'.$filename;

            return [
                'url' => asset($relativePath),
                'name' => $photo->getClientOriginalName(),
            ];
        })->values();

        return response()->json([
            'photos' => $photos,
        ]);
    }

    private function isAllowedComponent(string $featureType, string $componentType): bool
    {
        return $featureType === 'node'
            ? in_array($componentType, self::NODE_TYPES, true)
            : in_array($componentType, self::LINK_TYPES, true);
    }

    private function assertValidCoordinates(array $geometry): void
    {
        $coordinates = $geometry['coordinates'] ?? [];

        if ($geometry['type'] === 'Point') {
            abort_unless($this->isLngLat($coordinates), 422, 'Invalid point coordinates.');

            return;
        }

        abort_unless(count($coordinates) >= 2, 422, 'Fiber cable links need at least two coordinates.');

        foreach ($coordinates as $coordinate) {
            abort_unless($this->isLngLat($coordinate), 422, 'Invalid line coordinates.');
        }
    }

    private function isLngLat(mixed $coordinate): bool
    {
        if (! is_array($coordinate) || count($coordinate) < 2) {
            return false;
        }

        $lng = (float) $coordinate[0];
        $lat = (float) $coordinate[1];

        return $lng >= -180 && $lng <= 180 && $lat >= -90 && $lat <= 90;
    }

    private function normalizeProperties(array $properties, string $featureType, string $componentType): array
    {
        $allowed = [
            'id',
            'feature_type',
            'component_type',
            'name',
            'brand',
            'ip_address',
            'total_ports',
            'available_ports',
            'splitter_parent_tj_box_id',
            'splitter_parent_tj_box_name',
            'splitter_type',
            'parent_olt_port',
            'splitter_input_fiber_code',
            'splitter_input_tube_color',
            'splitter_input_core_color',
            'splitter_ports',
            'splitter_output_map',
            'splice_details',
            'box_name',
            'client_name',
            'address',
            'fiber_core_color',
            'connected_port',
            'connected_ports',
            'port_links',
            'olt_port_links',
            'fiber_code',
            'core_count',
            'core_mappings',
            'cable_type',
            'a_end_device_port',
            'a_end_tube_color',
            'a_end_core_color',
            'z_end_device_port',
            'z_end_tube_color',
            'z_end_core_color',
            'splitter_input_port',
            'splitter_output_port',
            'splitter_output_core_color',
            'connected_fiber_code',
            'connected_fiber_core_color',
            'endpoint_links',
            'length_meters',
            'photos',
            'notes',
        ];

        $clean = Arr::only($properties, $allowed);
        $clean['feature_type'] = $featureType;
        $clean['component_type'] = $componentType;

        foreach (['total_ports', 'available_ports'] as $key) {
            if (array_key_exists($key, $clean) && $clean[$key] !== null && $clean[$key] !== '') {
                $clean[$key] = max(0, (int) $clean[$key]);
            }
        }

        return $clean;
    }

    private function displayName(array $properties, string $componentType): ?string
    {
        return match ($componentType) {
            'tj_box' => $properties['box_name'] ?? $properties['name'] ?? null,
            'onu' => $properties['client_name'] ?? $properties['name'] ?? null,
            'fiber_cable' => $properties['fiber_code'] ?? $properties['name'] ?? null,
            default => $properties['name'] ?? null,
        };
    }

    private function customerToFeature(Customer $customer): array
    {
        $customer->loadMissing('importedSecret');
        $comment = trim((string) ($customer->importedSecret?->router_comment ?: ''));
        $hasLocation = ! is_null($customer->map_latitude) && ! is_null($customer->map_longitude);

        return [
            'type' => 'Feature',
            'id' => 'customer-'.$customer->id,
            'geometry' => $hasLocation ? [
                'type' => 'Point',
                'coordinates' => [(float) $customer->map_longitude, (float) $customer->map_latitude],
            ] : null,
            'properties' => [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'name' => (string) $customer->id,
                'comment' => $comment,
                'phone' => $customer->phone,
                'connection_id' => $customer->connection_id,
                'mikrotik_username' => $customer->mikrotik_username,
                'address' => $customer->address,
                'status' => $customer->status,
                'has_map_location' => $hasLocation,
                'show_url' => route('customers.show', $customer),
                'edit_url' => route('customers.edit', $customer),
            ],
        ];
    }
}
