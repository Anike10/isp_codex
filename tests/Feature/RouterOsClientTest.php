<?php

namespace Tests\Feature;

use App\Services\RouterOsClient;
use RuntimeException;
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

    public function test_trap_response_is_drained_through_done_before_the_next_command(): void
    {
        $client = new class extends RouterOsClient
        {
            public array $writtenSentences = [];

            public array $incomingSentences = [];

            protected function writeSentence(array $words): void
            {
                $this->writtenSentences[] = $words;
            }

            protected function readSentence(): array
            {
                return array_shift($this->incomingSentences) ?? [];
            }
        };

        $client->incomingSentences = [
            ['!trap', '=message=invalid value', '=category=1'],
            ['!done'],
            ['!re', '=.id=*A', '=name=party-a'],
            ['!done'],
        ];

        try {
            $client->command('/ppp/secret/set', ['.id' => '*A', 'remote-address' => 'bad']);
            $this->fail('The trapped RouterOS command should throw.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('invalid value', $exception->getMessage());
        }

        $records = $client->command('/ppp/secret/print', ['?name' => 'party-a']);

        $this->assertSame([['.id' => '*A', 'name' => 'party-a']], $records);
        $this->assertCount(2, $client->writtenSentences);
    }

    public function test_empty_reply_is_drained_until_done(): void
    {
        $client = new class extends RouterOsClient
        {
            public array $incomingSentences = [
                ['!empty'],
                ['!done'],
                ['!re', '=name=next-record'],
                ['!done'],
            ];

            protected function writeSentence(array $words): void {}

            protected function readSentence(): array
            {
                return array_shift($this->incomingSentences) ?? [];
            }
        };

        $this->assertSame([], $client->command('/ppp/secret/set', ['.id' => '*A']));
        $this->assertSame([['name' => 'next-record']], $client->command('/ppp/secret/print'));
    }
}
