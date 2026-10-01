<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiPhotoAdviceTest extends TestCase
{
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a4WQAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        config(['services.gemini.key' => 'test-key', 'services.gemini.model' => 'test-model']);
        Http::preventStrayRequests();
    }

    public function test_photo_with_optional_question_is_sent_to_gemini(): void
    {
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Please provide a clearer photo.']]]]]])]);
        foreach ([null, 'What should I check?'] as $question) {
            $photo = UploadedFile::fake()->createWithContent('goat.png', base64_decode(self::PNG));
            $this->post('/api/gemini-advice', ['photo' => $photo, 'question' => $question], ['Accept' => 'application/json'])
                ->assertOk()->assertJsonPath('advice', 'Please provide a clearer photo.');
        }
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request['contents'][0]['parts'][1]['inlineData'] === [
            'mimeType' => 'image/png', 'data' => self::PNG,
        ] && str_contains($request['contents'][0]['parts'][0]['text'], 'What should I check?'));
    }

    public function test_invalid_photo_and_empty_request_do_not_reach_gemini(): void
    {
        $this->postJson('/api/gemini-advice', [])->assertStatus(422);
        $this->post('/api/gemini-advice', ['photo' => UploadedFile::fake()->createWithContent('fake.png', '<script>bad</script>')], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('photo');
        $this->post('/api/gemini-advice', ['photo' => UploadedFile::fake()->create('large.png', 2049, 'image/png')], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('photo');
        Http::assertNothingSent();
    }
}
