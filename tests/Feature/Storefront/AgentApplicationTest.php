<?php

use App\Enums\AgentApplicationStatus;
use App\Enums\AgentStatus;
use App\Models\Agent;
use App\Models\AgentApplication;
use Database\Seeders\SettingsSeeder;
use Database\Seeders\VendorSubscriptionPlanSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('the agent application form loads', function () {
    $this->get(route('agent.apply'))->assertOk()->assertSee('Become a');
});

test('submitting a complete agent application redirects to the submitted page', function () {
    Storage::fake('local');

    $response = $this->post(route('agent.apply'), [
        'full_name' => 'Jane Agent',
        'email' => 'jane.agent@example.com',
        'identity_document' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
        'terms' => '1',
    ]);

    $response->assertRedirect(route('agent.apply.submitted'));
    expect(AgentApplication::where('email', 'jane.agent@example.com')->exists())->toBeTrue();
});

test('an application with an email still pending review is rejected', function () {
    AgentApplication::factory()->create(['email' => 'pending@example.com']);

    $response = $this->post(route('agent.apply'), [
        'full_name' => 'Pending Again',
        'email' => 'pending@example.com',
        'identity_document' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
        'terms' => '1',
    ]);

    $response->assertSessionHasErrors('email');
});

test('an email from a rejected agent application can apply again', function () {
    Storage::fake('local');
    AgentApplication::factory()->rejected()->create(['email' => 'rejected@example.com']);

    $response = $this->post(route('agent.apply'), [
        'full_name' => 'Second Try',
        'email' => 'rejected@example.com',
        'identity_document' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
        'terms' => '1',
    ]);

    $response->assertRedirect(route('agent.apply.submitted'));
    expect(AgentApplication::where('email', 'rejected@example.com')->where('status', AgentApplicationStatus::Pending)->exists())->toBeTrue();
});

test('an email from an approved agent application whose agent record was deleted can apply again', function () {
    Storage::fake('local');
    $agent = Agent::factory()->create(['status' => AgentStatus::Approved]);
    $application = AgentApplication::factory()->approved()->create(['email' => 'reapply@example.com', 'agent_id' => $agent->id]);
    $agent->delete();
    expect($application->refresh()->agent_id)->toBeNull();

    $response = $this->post(route('agent.apply'), [
        'full_name' => 'Reapplying',
        'email' => 'reapply@example.com',
        'identity_document' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
        'terms' => '1',
    ]);

    $response->assertRedirect(route('agent.apply.submitted'));
});

test('the vendor application page lists approved agents in the registered-agent dropdown', function () {
    (new SettingsSeeder)->run();
    (new VendorSubscriptionPlanSeeder)->run();

    $agent = Agent::factory()->create(['full_name' => 'Roster Agent', 'status' => AgentStatus::Approved]);
    $suspended = Agent::factory()->suspended()->create(['full_name' => 'Suspended Agent']);

    $response = $this->get(route('vendor.register'));

    $response->assertOk()
        ->assertSee($agent->full_name)
        ->assertDontSee($suspended->full_name);
});
