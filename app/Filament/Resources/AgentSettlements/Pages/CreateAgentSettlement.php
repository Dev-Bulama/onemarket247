<?php

namespace App\Filament\Resources\AgentSettlements\Pages;

use App\Actions\Settlement\CreateAgentSettlementAction;
use App\Filament\Resources\AgentSettlements\AgentSettlementResource;
use App\Models\Agent;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAgentSettlement extends CreateRecord
{
    protected static string $resource = AgentSettlementResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $agent = Agent::findOrFail($data['agent_id']);

        return app(CreateAgentSettlementAction::class)->handle($agent, $data['amount'], auth()->user());
    }
}
