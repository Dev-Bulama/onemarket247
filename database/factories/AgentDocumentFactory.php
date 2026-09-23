<?php

namespace Database\Factories;

use App\Enums\AgentDocumentStatus;
use App\Enums\AgentDocumentType;
use App\Models\AgentDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentDocument>
 */
class AgentDocumentFactory extends Factory
{
    protected $model = AgentDocument::class;

    public function definition(): array
    {
        return [
            'type' => AgentDocumentType::Identity,
            'file_path' => 'agent-documents/'.fake()->uuid().'.pdf',
            'status' => AgentDocumentStatus::Pending,
        ];
    }
}
