<?php

namespace App\Filament\Resources\ProductQuestions\Pages;

use App\Filament\Resources\ProductQuestions\ProductQuestionResource;
use Filament\Resources\Pages\ListRecords;

class ListProductQuestions extends ListRecords
{
    protected static string $resource = ProductQuestionResource::class;
}
