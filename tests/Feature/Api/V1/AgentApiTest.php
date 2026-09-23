<?php

use App\Enums\AgentStatus;
use App\Models\Agent;
use App\Models\AgentApplication;
use App\Models\VendorApplication;
use Database\Seeders\SettingsSeeder;
use Database\Seeders\VendorSubscriptionPlanSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('a complete agent application is accepted and stored as pending', function () {
    Notification::fake();

    $response = $this->postJson('/api/v1/agent/apply', [
        'full_name' => 'Jane Agent',
        'email' => 'jane.agent@example.com',
        'phone' => '+15551234567',
        'identity_document' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
        'terms' => '1',
    ]);

    $response->assertCreated()->assertJsonPath('data.status', 'pending');

    expect(AgentApplication::where('email', 'jane.agent@example.com')->exists())->toBeTrue();
});

test('an agent application without an identity document is rejected', function () {
    $this->postJson('/api/v1/agent/apply', [
        'full_name' => 'Jane Agent',
        'email' => 'jane.agent@example.com',
        'terms' => '1',
    ])->assertStatus(422)->assertJsonValidationErrors('identity_document');
});

test('an agent application without accepting terms is rejected', function () {
    $this->postJson('/api/v1/agent/apply', [
        'full_name' => 'Jane Agent',
        'email' => 'jane.agent@example.com',
        'identity_document' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
    ])->assertStatus(422)->assertJsonValidationErrors('terms');
});

test('the public agents endpoint lists only approved agents', function () {
    $approved = Agent::factory()->create(['full_name' => 'Approved Agent']);
    Agent::factory()->suspended()->create(['full_name' => 'Suspended Agent']);
    Agent::factory()->deactivated()->create(['full_name' => 'Deactivated Agent']);

    $response = $this->getJson('/api/v1/agents');

    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.full_name', 'Approved Agent');
});

test('a vendor application can reference a registered agent', function () {
    (new SettingsSeeder)->run();
    (new VendorSubscriptionPlanSeeder)->run();

    $agent = Agent::factory()->create(['status' => AgentStatus::Approved]);

    $response = $this->postJson('/api/v1/vendor/apply', [
        'full_name' => 'Jane Doe',
        'email' => 'jane.vendor@example.com',
        'business_name' => 'Jane Co',
        'store_name' => 'Jane Store',
        'bank_name' => 'First Bank',
        'bank_account_name' => 'Jane Doe',
        'bank_account_number' => '1234567890',
        'agent_id' => $agent->id,
        'identity_document' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
        'business_registration_document' => UploadedFile::fake()->create('reg.pdf', 100, 'application/pdf'),
        'terms' => '1',
    ]);

    $response->assertCreated();

    $application = VendorApplication::where('email', 'jane.vendor@example.com')->firstOrFail();
    expect($application->agent_id)->toBe($agent->id);
});
