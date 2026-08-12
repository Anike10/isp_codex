<?php

namespace Tests\Feature;

use App\Services\RouterOsClient;
use Tests\TestCase;

class RouterOsClientTest extends TestCase
{
    public function test_format_trap_exception_message_contains_reply_and_category_details(): void
    {
        $client = new RouterOsClient;
        $method = new \ReflectionMethod(RouterOsClient::class, 'formatTrapExceptionMessage');

        $message = $method->invoke($client, [
            'message' => 'RouterOS returned an error.',
            'reply' => 'The MAC is invalid.',
            'detail' => 'attribute .id must be unique.',
            'category' => 'generic',
        ]);

        $this->assertStringContainsString('RouterOS returned an error.', $message);
        $this->assertStringContainsString('reply: The MAC is invalid.', $message);
        $this->assertStringContainsString('detail: attribute .id must be unique.', $message);
        $this->assertStringContainsString('category: generic', $message);
    }
}

