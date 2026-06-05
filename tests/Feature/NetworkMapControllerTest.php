<?php

namespace Tests\Feature;

use App\Models\NetworkMapFeature;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class NetworkMapControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_network_map_topology_can_be_saved_and_returned_as_geojson(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());

        $payload = [
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'id' => 'router-1',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [90.4125, 23.8103],
                    ],
                    'properties' => [
                        'id' => 'router-1',
                        'feature_type' => 'node',
                        'component_type' => 'router',
                        'name' => 'Core Router',
                        'brand' => 'MikroTik',
                        'ip_address' => '10.10.10.1',
                        'total_ports' => 24,
                        'available_ports' => 6,
                        'connected_ports' => [
                            [
                                'fiber_id' => 'fiber-1',
                                'endpoint' => 'z',
                                'port' => '3',
                            ],
                            [
                                'fiber_id' => 'fiber-2',
                                'endpoint' => 'z',
                                'port' => '4',
                            ],
                        ],
                        'photos' => [
                            [
                                'url' => 'http://127.0.0.1/network-map-photos/router.jpg',
                                'name' => 'router.jpg',
                            ],
                        ],
                    ],
                ],
                [
                    'type' => 'Feature',
                    'id' => 'fiber-1',
                    'geometry' => [
                        'type' => 'LineString',
                        'coordinates' => [
                            [90.4125, 23.8103],
                            [90.4145, 23.8123],
                        ],
                    ],
                    'properties' => [
                        'id' => 'fiber-1',
                        'feature_type' => 'link',
                        'component_type' => 'fiber_cable',
                        'core_count' => '12F',
                        'core_mappings' => [
                            [
                                'key' => 'core-1',
                                'tube' => 1,
                                'core' => 1,
                                'color_name' => 'Blue',
                                'color_hex' => '#1d4ed8',
                                'in_point' => 'OLT-01 PON 1',
                                'out_point' => 'SP-01 IN',
                                'note' => 'Feeder core',
                            ],
                        ],
                        'cable_type' => 'Overhead',
                        'fiber_code' => 'F-OLT-SPL-001',
                        'a_end_tube_color' => 'Blue',
                        'a_end_core_color' => 'Blue',
                        'z_end_tube_color' => 'Blue',
                        'z_end_core_color' => 'Orange',
                        'splitter_input_port' => 'SP-01 IN',
                        'splitter_output_port' => 'OUT-01',
                        'splitter_output_core_color' => 'Green',
                        'connected_fiber_code' => 'DC-ONU-001',
                        'connected_fiber_core_color' => 'Green',
                        'endpoint_links' => [
                            'z' => [
                                'node_id' => 'tj-1',
                                'node_type' => 'tj_box',
                                'node_name' => 'TJ-BOX-01',
                                'port' => '3',
                            ],
                        ],
                        'length_meters' => 310.5,
                    ],
                ],
            ],
        ];

        $this->actingAs($user)
            ->postJson(route('network-map.features.store'), $payload)
            ->assertOk()
            ->assertJsonPath('type', 'FeatureCollection')
            ->assertJsonCount(2, 'features');

        $this->assertDatabaseHas('network_map_features', [
            'feature_uuid' => 'router-1',
            'feature_type' => 'node',
            'component_type' => 'router',
            'name' => 'Core Router',
        ]);

        $this->actingAs($user)
            ->getJson(route('network-map.features.index'))
            ->assertOk()
            ->assertJsonPath('type', 'FeatureCollection')
            ->assertJsonFragment(['fiber_code' => 'F-OLT-SPL-001'])
            ->assertJsonFragment(['out_point' => 'SP-01 IN'])
            ->assertJsonFragment(['node_name' => 'TJ-BOX-01'])
            ->assertJsonFragment(['fiber_id' => 'fiber-2'])
            ->assertJsonFragment(['name' => 'router.jpg'])
            ->assertJsonCount(2, 'features');
    }

    public function test_network_map_photos_can_be_uploaded(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());

        File::deleteDirectory(public_path('network-map-photos'));

        $this->actingAs($user)
            ->post(route('network-map.photos.store'), [
                'photos' => [
                    $this->fakePng('tj-box.png'),
                ],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'photos')
            ->assertJsonFragment(['name' => 'tj-box.png']);

        File::deleteDirectory(public_path('network-map-photos'));
    }

    public function test_invalid_geometry_is_rejected(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_mikrotik_routers')->firstOrFail());

        $this->actingAs($user)
            ->postJson(route('network-map.features.store'), [
                'type' => 'FeatureCollection',
                'features' => [
                    [
                        'type' => 'Feature',
                        'id' => 'bad-node',
                        'geometry' => [
                            'type' => 'Point',
                            'coordinates' => [200, 95],
                        ],
                        'properties' => [
                            'component_type' => 'router',
                        ],
                    ],
                ],
            ])
            ->assertStatus(422);

        $this->assertSame(0, NetworkMapFeature::count());
    }

    private function fakePng(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'network-map-photo');
        file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='
        ));

        return new UploadedFile($path, $name, 'image/png', null, true);
    }
}
