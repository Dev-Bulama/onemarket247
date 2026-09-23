<?php

namespace App\Actions\Agent;

use App\Enums\AgentDocumentType;
use App\Models\AgentApplication;
use App\Models\AgentDocument;
use App\Notifications\AgentApplicationReceivedNotification;
use App\Notifications\NewAgentApplicationNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Creates the AgentApplication + its AgentDocument rows from the public
 * application form. Unlike vendor applications there is no "automatic
 * approval" mode here — an agent always needs a human admin decision (see
 * ApproveAgentApplicationAction), since approving one hands out a slot in
 * the "Registered Agent" dropdown vendors pick from.
 */
class SubmitAgentApplicationAction
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile|null>  $documents  keyed by AgentDocumentType value
     */
    public function handle(array $data, array $documents): AgentApplication
    {
        $application = DB::transaction(function () use ($data, $documents) {
            $application = AgentApplication::create($data);

            foreach ($documents as $type => $file) {
                if (! $file) {
                    continue;
                }

                $path = $file->store("agent-documents/{$application->id}", 'local');

                AgentDocument::create([
                    'agent_application_id' => $application->id,
                    'type' => AgentDocumentType::from($type),
                    'file_path' => $path,
                ]);
            }

            return $application->fresh();
        });

        try {
            Notification::route('mail', $application->email)
                ->notify(new AgentApplicationReceivedNotification($application));
        } catch (Throwable $exception) {
            report($exception);
        }

        try {
            Notification::route('mail', config('mail.from.address'))
                ->notify(new NewAgentApplicationNotification($application));
        } catch (Throwable $exception) {
            report($exception);
        }

        return $application;
    }
}
