<?php

namespace App\Http\Requests\Agent;

use App\Enums\AgentApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class AgentApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:agents,email', $this->uniqueLiveApplicationEmail()],
            'phone' => ['nullable', 'string', 'max:30'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'state_id' => ['nullable', 'exists:states,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'identity_type' => ['nullable', 'string', 'max:100'],
            'identity_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'identity_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'proof_of_address_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],

            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'terms.accepted' => 'You must accept the agent terms to continue.',
        ];
    }

    /**
     * A rejected application, or an approved one whose agent record was
     * later permanently deleted by an admin, must never block the same
     * person from applying again — only a still-pending application, or an
     * approved one with a live agent record, is a genuine duplicate.
     */
    private function uniqueLiveApplicationEmail(): Unique
    {
        return Rule::unique('agent_applications', 'email')->where(function ($query) {
            $query->where('status', AgentApplicationStatus::Pending)
                ->orWhere(function ($query) {
                    $query->where('status', AgentApplicationStatus::Approved)
                        ->whereNotNull('agent_id');
                });
        });
    }
}
