<?php

use App\Models\Collection;
use App\Models\Product;

test('a collection page lists only products in that collection', function () {
    $collection = Collection::factory()->create();
    $inCollection = Product::factory()->create();
    $inCollection->collections()->attach($collection->id);
    $notInCollection = Product::factory()->create();

    $response = $this->get(route('collections.show', $collection));

    $response->assertOk()->assertSee($inCollection->name)->assertDontSee($notInCollection->name);
});
