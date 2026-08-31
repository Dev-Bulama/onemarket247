<?php

use App\Enums\BlogPostStatus;
use App\Filament\Resources\BlogPosts\Pages\CreateBlogPost;
use App\Models\BlogPost;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function blogAdminUser(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $user;
}

test('an admin can load the blog posts index and create pages', function () {
    $admin = blogAdminUser();

    $this->actingAs($admin, 'admin')->get('/admin/blog-posts')->assertOk();
    $this->actingAs($admin, 'admin')->get('/admin/blog-posts/create')->assertOk();
});

test('an admin without blog.manage cannot access blog posts', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Support Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/blog-posts')->assertForbidden();
});

test('an admin can create a blog post with a cover image and it is stamped with their id as author', function () {
    Storage::fake('public');
    $admin = blogAdminUser();

    Livewire::actingAs($admin, 'admin')
        ->test(CreateBlogPost::class)
        ->fillForm([
            'title' => 'How to Shop Smart',
            'slug' => 'how-to-shop-smart',
            'body' => 'Some helpful shopping tips.',
            'status' => BlogPostStatus::Published->value,
            'cover' => [UploadedFile::fake()->image('cover.jpg')->store('tmp-blog-media', 'public')],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $post = BlogPost::where('slug', 'how-to-shop-smart')->firstOrFail();

    expect($post->author_id)->toBe($admin->id)
        ->and($post->getFirstMediaUrl('cover'))->not->toBe('');
});

test('a post created as Published without an explicit published_at is auto-stamped and visible publicly', function () {
    $admin = blogAdminUser();

    Livewire::actingAs($admin, 'admin')
        ->test(CreateBlogPost::class)
        ->fillForm([
            'title' => 'Auto Published Post',
            'slug' => 'auto-published-post',
            'body' => 'Body text.',
            'status' => BlogPostStatus::Published->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $post = BlogPost::where('slug', 'auto-published-post')->firstOrFail();

    expect($post->published_at)->not->toBeNull()
        ->and($post->published_at->lessThanOrEqualTo(now()))->toBeTrue();

    $this->get('/blog')->assertOk()->assertSee('Auto Published Post');
});

test('a post created as Draft is not auto-stamped with published_at', function () {
    $admin = blogAdminUser();

    Livewire::actingAs($admin, 'admin')
        ->test(CreateBlogPost::class)
        ->fillForm([
            'title' => 'Still A Draft',
            'slug' => 'still-a-draft',
            'body' => 'Body text.',
            'status' => BlogPostStatus::Draft->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(BlogPost::where('slug', 'still-a-draft')->firstOrFail()->published_at)->toBeNull();
});
