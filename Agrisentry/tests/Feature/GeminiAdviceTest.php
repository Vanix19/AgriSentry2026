<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiAdviceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        config(['services.gemini.key' => 'test-key', 'services.gemini.model' => 'test-model']);
        Http::preventStrayRequests();
    }

    public function test_it_uses_configured_credentials_and_returns_all_answer_parts(): void
    {
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [
            ['text' => 'Internal thought', 'thought' => true],
            ['text' => 'First part.'], ['text' => 'Second part.'],
        ]]]]])]);

        $this->postJson('/api/gemini-advice', ['question' => 'How do I monitor my goat?'])
            ->assertOk()->assertJsonPath('advice', "First part.\nSecond part.");

        Http::assertSent(fn ($request) => $request->hasHeader('x-goog-api-key', 'test-key')
            && $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/test-model:generateContent');
    }

    public function test_quota_failure_is_not_reported_as_success(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'private upstream detail']], 429)]);
        $this->postJson('/api/gemini-advice', ['question' => 'Hello'])
            ->assertStatus(503)->assertJsonPath('message', 'Gemini quota or rate limit reached. Please try again later.');
    }

    public function test_replies_preserve_introduction_bold_labels_and_bullets_without_headings(): void
    {
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [[
            'text' => "### Blue LED\n**Low** means below 33.0°C.\n\n* **Check:** Recheck the collar.\n1. **Observe:** Monitor closely.",
        ]]]]]])]);
        $this->postJson('/api/gemini-advice', ['question' => 'What does blue mean?'])
            ->assertOk()->assertJsonPath('advice', "**Low** means below 33.0°C.\n\n• **Check:** Recheck the collar.\n• **Observe:** Monitor closely.");
        Http::assertSent(fn ($request) => str_contains($request['systemInstruction']['parts'][0]['text'], 'under 100 words'));
    }

    public function test_empty_response_is_not_reported_as_success(): void
    {
        Http::fake(['*' => Http::response(['promptFeedback' => ['blockReason' => 'SAFETY']])]);
        $this->postJson('/api/gemini-advice', ['question' => 'Hello'])->assertStatus(503);
    }

    public function test_selected_interface_language_is_sent_with_the_advice_request(): void
    {
        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'Suriin ang kambing.']]]]],
        ])]);
        $this->postJson('/api/gemini-advice', ['question' => 'How is my goat?', 'language' => 'fil'])
            ->assertOk()->assertJsonPath('advice', 'Suriin ang kambing.');
        Http::assertSent(fn ($request) => str_contains($request['contents'][0]['parts'][0]['text'], 'Write the entire answer in Filipino'));
    }

    public function test_language_must_be_a_supported_value(): void
    {
        $this->postJson('/api/gemini-advice', ['question' => 'Hello', 'language' => 'invalid'])
            ->assertUnprocessable()->assertJsonValidationErrors('language');
        Http::assertNothingSent();
    }

    public function test_multilingual_replies_preserve_unicode_and_formatting(): void
    {
        $advice = "Ang **asul na LED** ay mababang temperatura.\n\n• **Suriin:** Tingnan kung mas mababa sa 33.0°C.\n• **观察：** 检查传感器。";
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => $advice]]]]]])]);
        $this->postJson('/api/gemini-advice', ['question' => 'Ano ang ibig sabihin ng asul na LED?'])
            ->assertOk()->assertJsonPath('advice', $advice);
        Http::assertSent(fn ($request) => str_contains($request['systemInstruction']['parts'][0]['text'], 'language explicitly requested')
            && str_contains($request['systemInstruction']['parts'][0]['text'], 'language of the user question'));
    }

    public function test_connection_errors_do_not_expose_credentials(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('secret-key'));
        $this->postJson('/api/gemini-advice', ['question' => 'Hello'])
            ->assertStatus(500)->assertJsonMissingPath('error')->assertDontSee('secret-key');
    }
}
