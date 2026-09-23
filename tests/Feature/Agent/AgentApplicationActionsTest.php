<?php

use App\Actions\Agent\ApproveAgentApplicationAction;
use App\Actions\Agent\RejectAgentApplicationAction;
use App\Actions\Agent\SubmitAgentApplicationAction;
use App\Enums\AgentApplicationStatus;
use App\Enums\AgentDocumentType;
use App\Enums\AgentStatus;
use App\Exceptions\AgentApplicationConflictException;
use App\Models\Agent;
use App\Models\AgentApplication;
use App\Models\AgentDocument;
use App\Notifications\AgentApplicationApprovedNotification;
use App\Notifications\AgentApplicationReceivedNotification;
use App\Notifications\AgentApplicationRejectedNotification;
use App\Notifications\NewAgentApplicationNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

test('submitting an application creates it as pending, stores documents, and notifies applicant and admin', function () {
    Notification::fake();
    Storage::fake('local');

    $action = app(SubmitAgentApplicationAction::class);
    $application = $action->handle([
        'full_name' => 'Jane Agent',
        'email' => 'jane@example.com',
        'phone' => '+15551234',
    ], [
        AgentDocumentType::Identity->value => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
    ]);

    expect($application->status)->toBe(AgentApplicationStatus::Pending)
        ->and($application->reference_number)->toStartWith('AA-')
        ->and($application->documents)->toHaveCount(1);

    Notification::assertSentOnDemand(AgentApplicationReceivedNotification::class);
    Notification::assertSentOnDemand(NewAgentApplicationNotification::class);
});

test('approving an application provisions an agent and migrates its documents', function () {
    Notification::fake();

    $application = AgentApplication::factory()->create();
    AgentDocument::factory()->create(['agent_application_id' => $application->id]);

    $agent = app(ApproveAgentApplicationAction::class)->handle($application);

    expect($agent->full_name)->toBe($application->full_name)
        ->and($agent->email)->toBe($application->email)
        ->and($agent->status)->toBe(AgentStatus::Approved)
        ->and($agent->approved_at)->not->toBeNull();

    $application->refresh();
    expect($application->status)->toBe(AgentApplicationStatus::Approved)
        ->and($application->agent_id)->toBe($agent->id);

    $document = AgentDocument::first();
    expect($document->agent_id)->toBe($agent->id)
        ->and($document->agent_application_id)->toBeNull();

    Notification::assertSentOnDemand(AgentApplicationApprovedNotification::class);
});

test('approving an application whose email already belongs to another agent throws a conflict exception', function () {
    Agent::factory()->create(['email' => 'taken@example.com']);
    $application = AgentApplication::factory()->create(['email' => 'taken@example.com']);

    expect(fn () => app(ApproveAgentApplicationAction::class)->handle($application))
        ->toThrow(AgentApplicationConflictException::class);

    expect($application->fresh()->status)->toBe(AgentApplicationStatus::Pending);
});

test('rejecting an application sets the reason and notifies the applicant', function () {
    Notification::fake();
    $application = AgentApplication::factory()->create();

    app(RejectAgentApplicationAction::class)->handle($application, 'Documents unclear.');

    expect($application->fresh()->status)->toBe(AgentApplicationStatus::Rejected)
        ->and($application->fresh()->rejection_reason)->toBe('Documents unclear.');

    Notification::assertSentOnDemand(AgentApplicationRejectedNotification::class);
});
