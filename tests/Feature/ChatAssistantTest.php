<?php

namespace Tests\Feature;

use Database\Seeders\CatalogSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $this->seed([CatalogSeeder::class, PaymentMethodSeeder::class]);
    }

    private function ask(string $text = 'Any gifts under $10?')
    {
        return $this->postJson('/api/chat/assistant', ['messages' => [['role' => 'user', 'text' => $text]]]);
    }

    public function test_without_a_key_the_page_is_told_to_use_built_in_replies(): void
    {
        config(['services.gemini.key' => null]);

        $this->ask()->assertStatus(503);
    }

    public function test_gemini_answers_with_the_shop_catalog_in_its_instructions(): void
    {
        config(['services.gemini.key' => 'test-key', 'services.gemini.model' => 'gemini-test']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Try the Kawaii Bunny Plush Bag Charm Duo, $6.00 product-detail.html?id=genz-24']]]]],
            ]),
        ]);

        $this->ask()->assertOk()->assertJsonPath('reply', 'Try the Kawaii Bunny Plush Bag Charm Duo, $6.00 product-detail.html?id=genz-24');

        Http::assertSent(function (HttpRequest $request) {
            $instructions = $request['system_instruction']['parts'][0]['text'];

            return str_contains($request->url(), 'models/gemini-test:generateContent')
                && $request->hasHeader('x-goog-api-key', 'test-key')
                && str_contains($instructions, 'product-detail.html?id=genz-24')
                && str_contains($instructions, 'free on orders of $15')
                && $request['contents'][0]['role'] === 'user';
        });
    }

    public function test_gemini_errors_come_back_as_a_friendly_message(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'quota']], 429)]);

        $this->ask()->assertStatus(502)->assertJsonPath('message', 'The assistant is busy right now. Please try again in a minute.');
    }

    public function test_backup_model_answers_when_the_main_model_is_rate_limited(): void
    {
        config(['services.gemini.key' => 'test-key', 'services.gemini.model' => 'main-model', 'services.gemini.fallback_model' => 'backup-model']);
        Http::fake([
            '*models/main-model:generateContent' => Http::response(['error' => ['message' => 'quota']], 429),
            '*models/backup-model:generateContent' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Try the bunny plush!']]]]]]),
        ]);

        $this->ask()->assertOk()->assertJsonPath('reply', 'Try the bunny plush!');
        Http::assertSentCount(2);
    }

    public function test_conversation_is_validated(): void
    {
        config(['services.gemini.key' => 'test-key']);

        $this->postJson('/api/chat/assistant', ['messages' => []])->assertStatus(422);
        $this->postJson('/api/chat/assistant', ['messages' => [['role' => 'system', 'text' => 'hi']]])->assertStatus(422);
        $this->postJson('/api/chat/assistant', ['messages' => [['role' => 'user', 'text' => str_repeat('a', 1501)]]])->assertStatus(422);
    }
}
