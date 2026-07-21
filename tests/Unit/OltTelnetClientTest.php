<?php

namespace Tests\Unit;

use App\Services\OltTelnetClient;
use ReflectionMethod;
use Tests\TestCase;

class OltTelnetClientTest extends TestCase
{
    public function test_authentication_failure_prompts_are_recognized(): void
    {
        $method = new ReflectionMethod(OltTelnetClient::class, 'showsAuthenticationFailure');
        $client = new OltTelnetClient;

        $this->assertTrue($method->invoke($client, "Login incorrect\nUsername:"));
        $this->assertTrue($method->invoke($client, 'Password:'));
        $this->assertFalse($method->invoke($client, 'US_EPON#'));
    }
}
