<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RelatedProductType: string implements HasLabel
{
    case Related = 'related';
    case UpSell = 'up_sell';
    case CrossSell = 'cross_sell';
    case FrequentlyBoughtTogether = 'frequently_bought_together';

    public function getLabel(): string
    {
        return match ($this) {
            self::Related => 'Related',
            self::UpSell => 'Upsell',
            self::CrossSell => 'Cross-sell',
            self::FrequentlyBoughtTogether => 'Frequently Bought Together',
        };
    }
}
