<?php

namespace App\Filament\Resources\AgentSettlements\Pages;

use App\Filament\Resources\AgentSettlements\AgentSettlementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAgentSettlements extends ListRecords
{
    protected static string $resource = AgentSettlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
