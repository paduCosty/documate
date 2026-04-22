<?php

namespace Tests\Feature\Extraction;

use App\Jobs\ProcessExtractionJob;
use App\Models\ExtractionJob;
use App\Models\ExtractionTemplate;
use App\Models\User;
use App\Services\Output\OutputFormatterFactory;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature tests for the Extraction backend (Phase 8).
 * Run with: php artisan test --filter=ExtractionBackendTest
 */
class ExtractionBackendTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
        $this->user = User::factory()->create();
        $this->seedTemplate();
        Queue::fake();
        Storage::fake('local');
    }

    // ─── ExtractionUploadRequest validation ──────────────────────────────────

    public function test_process_rejects_missing_file(): void
    {
        $this->actingAs($this->user)
            ->post(route('tools.extract-pdf.process'), ['template' => 'invoice'])
            ->assertSessionHasErrors('file');
    }

    public function test_process_rejects_non_pdf_file(): void
    {
        $this->actingAs($this->user)
            ->post(route('tools.extract-pdf.process'), [
                'file'     => UploadedFile::fake()->create('doc.docx', 100, 'application/msword'),
                'template' => 'invoice',
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_process_rejects_missing_template(): void
    {
        $this->actingAs($this->user)
            ->post(route('tools.extract-pdf.process'), [
                'file' => UploadedFile::fake()->create('test.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('template');
    }

    public function test_process_rejects_invalid_format(): void
    {
        $this->actingAs($this->user)
            ->post(route('tools.extract-pdf.process'), [
                'file'     => UploadedFile::fake()->create('test.pdf', 100, 'application/pdf'),
                'template' => 'invoice',
                'format'   => 'xml',
            ])
            ->assertSessionHasErrors('format');
    }

    public function test_process_rejects_unknown_template(): void
    {
        $this->actingAs($this->user)
            ->post(route('tools.extract-pdf.process'), [
                'file'     => UploadedFile::fake()->create('test.pdf', 100, 'application/pdf'),
                'template' => 'nonexistent_template',
                'format'   => 'json',
            ])
            ->assertStatus(422);
    }

    // ─── Successful upload ────────────────────────────────────────────────────

    public function test_process_creates_extraction_job_record(): void
    {
        $this->actingAs($this->user)
            ->post(route('tools.extract-pdf.process'), $this->validUploadPayload());

        $this->assertDatabaseHas('extraction_jobs', [
            'user_id'       => $this->user->id,
            'status'        => 'pending',
            'output_format' => 'json',
        ]);
    }

    public function test_process_dispatches_queue_job(): void
    {
        $this->actingAs($this->user)
            ->post(route('tools.extract-pdf.process'), $this->validUploadPayload());

        Queue::assertPushed(ProcessExtractionJob::class);
    }

    public function test_process_redirects_to_status_page(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('tools.extract-pdf.process'), $this->validUploadPayload());

        $job = ExtractionJob::where('user_id', $this->user->id)->latest()->first();

        $response->assertRedirect(route('extraction.status', $job->uuid));
    }

    public function test_process_stores_original_filename(): void
    {
        $this->actingAs($this->user)
            ->post(route('tools.extract-pdf.process'), $this->validUploadPayload('invoice.pdf'));

        $this->assertDatabaseHas('extraction_jobs', ['original_filename' => 'invoice.pdf']);
    }

    public function test_process_uses_default_format_when_not_specified(): void
    {
        $payload = $this->validUploadPayload();
        unset($payload['format']);

        $this->actingAs($this->user)
            ->post(route('tools.extract-pdf.process'), $payload);

        $this->assertDatabaseHas('extraction_jobs', [
            'output_format' => OutputFormatterFactory::defaultFormat(),
        ]);
    }

    // ─── Auth guards ──────────────────────────────────────────────────────────

    public function test_page_requires_authentication(): void
    {
        $this->get(route('tools.extract-pdf'))->assertRedirect(route('login'));
    }

    public function test_process_requires_authentication(): void
    {
        $this->post(route('tools.extract-pdf.process'), $this->validUploadPayload())
            ->assertRedirect(route('login'));
    }

    public function test_status_requires_authentication(): void
    {
        $this->get(route('extraction.status', 'some-uuid'))->assertRedirect(route('login'));
    }

    public function test_poll_requires_authentication(): void
    {
        $this->get(route('extraction.poll', 'some-uuid'))->assertRedirect(route('login'));
    }

    public function test_download_requires_authentication(): void
    {
        $this->get(route('extraction.download', 'some-uuid'))->assertRedirect(route('login'));
    }

    // ─── Status / Poll ────────────────────────────────────────────────────────

    public function test_poll_returns_json_for_owned_job(): void
    {
        $job = $this->makeJob();

        $this->actingAs($this->user)
            ->getJson(route('extraction.poll', $job->uuid))
            ->assertOk()
            ->assertJsonFragment(['uuid' => $job->uuid, 'status' => 'pending']);
    }

    public function test_poll_returns_404_for_another_users_job(): void
    {
        $otherUser = User::factory()->create();
        $job       = $this->makeJob($otherUser);

        $this->actingAs($this->user)
            ->getJson(route('extraction.poll', $job->uuid))
            ->assertNotFound();
    }

    public function test_poll_response_has_required_keys(): void
    {
        $job = $this->makeJob();

        $response = $this->actingAs($this->user)
            ->getJson(route('extraction.poll', $job->uuid))
            ->assertOk()
            ->json();

        foreach (['uuid', 'status', 'can_download', 'is_expired', 'expires_at'] as $key) {
            $this->assertArrayHasKey($key, $response, "Missing key: {$key}");
        }
    }

    public function test_status_page_renders_for_owned_job(): void
    {
        $job = $this->makeJob();

        // Only assert HTTP 200 — the Inertia component file doesn't exist yet (Phase 9).
        $this->actingAs($this->user)
            ->get(route('extraction.status', $job->uuid))
            ->assertOk();
    }

    // ─── Download ─────────────────────────────────────────────────────────────

    public function test_download_returns_404_for_job_with_no_output(): void
    {
        $job = $this->makeJob(status: 'pending');

        $this->actingAs($this->user)
            ->get(route('extraction.download', $job->uuid))
            ->assertStatus(400);
    }

    public function test_download_returns_410_for_expired_job(): void
    {
        $job             = $this->makeJob(status: 'completed');
        $job->output_path = '/tmp/fake.json';
        $job->expires_at  = now()->subHour();
        $job->save();

        $this->actingAs($this->user)
            ->get(route('extraction.download', $job->uuid))
            ->assertStatus(410);
    }

    // ─── ExtractionTemplateController ────────────────────────────────────────

    public function test_templates_index_requires_auth(): void
    {
        $this->get(route('extraction.templates.index'))->assertRedirect(route('login'));
    }

    public function test_templates_index_returns_templates_visible_to_user(): void
    {
        // Only assert HTTP 200 + templates prop — component file doesn't exist yet (Phase 9).
        $this->actingAs($this->user)
            ->get(route('extraction.templates.index'))
            ->assertOk();
    }

    public function test_templates_store_creates_template(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('extraction.templates.store'), $this->validTemplatePayload())
            ->assertCreated()
            ->assertJsonFragment(['name' => 'My Custom Template']);

        $this->assertDatabaseHas('extraction_templates', [
            'user_id'   => $this->user->id,
            'name'      => 'My Custom Template',
            'is_system' => false,
        ]);
    }

    public function test_templates_store_rejects_missing_placeholders(): void
    {
        $payload = $this->validTemplatePayload();
        $payload['prompt_template'] = 'No placeholders here at all.';

        $this->actingAs($this->user)
            ->postJson(route('extraction.templates.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('prompt_template');
    }

    public function test_templates_store_requires_auth(): void
    {
        $this->postJson(route('extraction.templates.store'), $this->validTemplatePayload())
            ->assertUnauthorized();
    }

    public function test_templates_update_owned_template(): void
    {
        $template = ExtractionTemplate::create([
            'user_id'         => $this->user->id,
            'slug'            => 'my-template',
            'name'            => 'Old Name',
            'prompt_template' => 'Extract {pdf_text}. Schema: {output_schema}',
            'output_schema'   => ['type' => 'object'],
            'is_system'       => false,
            'active'          => true,
        ]);

        $payload          = $this->validTemplatePayload();
        $payload['name']  = 'Updated Name';

        $this->actingAs($this->user)
            ->putJson(route('extraction.templates.update', $template->id), $payload)
            ->assertOk()
            ->assertJsonFragment(['name' => 'Updated Name']);
    }

    public function test_templates_update_rejects_system_template(): void
    {
        $system = ExtractionTemplate::where('is_system', true)->first();

        $this->actingAs($this->user)
            ->putJson(route('extraction.templates.update', $system->id), $this->validTemplatePayload())
            ->assertForbidden();
    }

    public function test_templates_update_rejects_other_users_template(): void
    {
        $otherUser = User::factory()->create();
        $template  = ExtractionTemplate::create([
            'user_id'         => $otherUser->id,
            'slug'            => 'other-template',
            'name'            => 'Other',
            'prompt_template' => 'Extract {pdf_text}. Schema: {output_schema}',
            'output_schema'   => ['type' => 'object'],
            'is_system'       => false,
            'active'          => true,
        ]);

        $this->actingAs($this->user)
            ->putJson(route('extraction.templates.update', $template->id), $this->validTemplatePayload())
            ->assertForbidden();
    }

    public function test_templates_destroy_owned_template(): void
    {
        $template = ExtractionTemplate::create([
            'user_id'         => $this->user->id,
            'slug'            => 'delete-me',
            'name'            => 'Delete Me',
            'prompt_template' => 'Extract {pdf_text}. Schema: {output_schema}',
            'output_schema'   => ['type' => 'object'],
            'is_system'       => false,
            'active'          => true,
        ]);

        $this->actingAs($this->user)
            ->deleteJson(route('extraction.templates.destroy', $template->id))
            ->assertOk()
            ->assertJsonFragment(['message' => 'Template deleted.']);

        $this->assertDatabaseMissing('extraction_templates', ['id' => $template->id]);
    }

    public function test_templates_destroy_rejects_system_template(): void
    {
        $system = ExtractionTemplate::where('is_system', true)->first();

        $this->actingAs($this->user)
            ->deleteJson(route('extraction.templates.destroy', $system->id))
            ->assertForbidden();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function validUploadPayload(string $filename = 'test.pdf'): array
    {
        return [
            'file'     => UploadedFile::fake()->create($filename, 200, 'application/pdf'),
            'template' => 'invoice',
            'format'   => 'json',
        ];
    }

    private function validTemplatePayload(): array
    {
        return [
            'name'            => 'My Custom Template',
            'description'     => 'Extracts data from invoices.',
            'prompt_template' => "Extract from: {pdf_text}\nSchema: {output_schema}",
            'output_schema'   => ['type' => 'object', 'properties' => []],
        ];
    }

    private function makeJob(?User $owner = null, string $status = 'pending'): ExtractionJob
    {
        return ExtractionJob::create([
            'user_id'           => ($owner ?? $this->user)->id,
            'original_filename' => 'invoice.pdf',
            'file_size_bytes'   => 1024,
            'status'            => $status,
            'output_format'     => 'json',
            'expires_at'        => now()->addHours(24),
        ]);
    }

    private function seedTemplate(): void
    {
        \Illuminate\Support\Facades\DB::table('extraction_templates')->updateOrInsert(
            ['slug' => 'invoice', 'user_id' => null],
            [
                'name'            => 'Invoice',
                'prompt_template' => 'Extract from: {pdf_text}. Schema: {output_schema}',
                'output_schema'   => json_encode(['type' => 'object']),
                'is_system'       => true,
                'active'          => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]
        );
    }
}
