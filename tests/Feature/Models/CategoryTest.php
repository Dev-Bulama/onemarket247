<?php

use App\Models\Category;

test('a root category has no path', function () {
    $root = Category::factory()->create();

    expect($root->path)->toBeNull()
        ->and($root->isRoot())->toBeTrue();
});

test('a childs path is its parents id', function () {
    $root = Category::factory()->create();
    $child = Category::factory()->childOf($root)->create();

    expect($child->path)->toBe((string) $root->id)
        ->and($child->isRoot())->toBeFalse();
});

test('a grandchilds path is the full ancestor chain', function () {
    $root = Category::factory()->create();
    $child = Category::factory()->childOf($root)->create();
    $grandchild = Category::factory()->childOf($child)->create();

    expect($grandchild->path)->toBe("{$root->id}/{$child->id}");
});

test('moving a category to a new parent recomputes its path', function () {
    $rootA = Category::factory()->create();
    $rootB = Category::factory()->create();
    $child = Category::factory()->childOf($rootA)->create();

    expect($child->path)->toBe((string) $rootA->id);

    $child->update(['parent_id' => $rootB->id]);

    expect($child->fresh()->path)->toBe((string) $rootB->id);
});
