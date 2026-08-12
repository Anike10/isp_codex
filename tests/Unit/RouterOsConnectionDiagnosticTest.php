<?php

namespace Tests\Unit;

use App\Models\MikrotikRouter;
use App\Services\RouterOsConnectionDiagnostic;
use RuntimeException;
use Tests\TestCase;

class RouterOsConnectionDiagnosticTest extends TestCase
{
    public function test_it_identifies_rejected_credentials_without_exposing_the_password(): void
    {
        $router = $this->router();
        $result = (new RouterOsConnectionDiagnostic)->describe(
            new RuntimeException('Authentication failed: invalid user name or password'),
            $router,
            true
        );

        $this->assertSame('credentials', $result['type']);
        $this->assertSame('Credential rejected', $result['label']);
        $this->assertStringContainsString("username 'admin'", $result['message']);
        $this->assertStringNotContainsString('router-secret', implode(' ', $result));
    }

    public function test_it_distinguishes_an_api_port_failure_when_ping_works(): void
    {
        $result = (new RouterOsConnectionDiagnostic)->describe(
            new RuntimeException('Network/port failure: connection refused'),
            $this->router(),
            true
        );

        $this->assertSame('network', $result['type']);
        $this->assertSame('API port unreachable', $result['label']);
        $this->assertStringContainsString('TCP 8787', $result['message']);
    }

    public function test_it_explains_when_an_imported_password_needs_to_be_reentered(): void
    {
        $result = (new RouterOsConnectionDiagnostic)->describe(
            new RuntimeException(MikrotikRouter::API_PASSWORD_REENTRY_MESSAGE),
            $this->router(),
            true
        );

        $this->assertSame('credentials', $result['type']);
        $this->assertSame('API password must be re-entered', $result['label']);
        $this->assertStringContainsString('Edit MikroTik Router', $result['guidance']);
        $this->assertStringNotContainsString('router-secret', implode(' ', $result));
    }

    private function router(): MikrotikRouter
    {
        return new MikrotikRouter([
            'name' => 'Main Router',
            'ip_address' => '103.133.200.180',
            'api_port' => 8787,
            'username' => 'admin',
            'password' => 'router-secret',
        ]);
    }
}
