<?php

use App\Models\Comment;
use Illuminate\Console\Scheduling\Schedule;

it('erases the IP of comments posted more than a year ago and keeps the comment', function () {
    $old = Comment::factory()->create(['author_ip' => '203.0.113.7', 'posted_at' => now()->subMonths(13)]);
    $recent = Comment::factory()->create(['author_ip' => '203.0.113.8', 'posted_at' => now()->subMonths(11)]);
    $oldWithoutDate = Comment::factory()->create(['author_ip' => '203.0.113.9', 'posted_at' => null, 'created_at' => now()->subMonths(13)]);
    $trashed = Comment::factory()->create(['author_ip' => '203.0.113.10', 'posted_at' => now()->subMonths(13)]);
    $trashed->delete();

    $this->artisan('comments:purge-ips')->assertSuccessful();

    expect($old->fresh()->author_ip)->toBeNull()
        ->and($old->fresh()->content)->not->toBeEmpty()
        ->and($recent->fresh()->author_ip)->toBe('203.0.113.8')
        ->and($oldWithoutDate->fresh()->author_ip)->toBeNull()
        ->and(Comment::withTrashed()->find($trashed->id)->author_ip)->toBeNull();
});

it('purges the comment IPs every day', function () {
    $scheduled = collect(app(Schedule::class)->events())
        ->contains(fn ($event): bool => str_ends_with((string) $event->command, 'comments:purge-ips'));

    expect($scheduled)->toBeTrue();
});

it('announces the IP retention period in the privacy policy', function () {
    $this->get(route('legal.privacy'))
        ->assertOk()
        ->assertSee('effacée un an après la publication du commentaire', false);
});
