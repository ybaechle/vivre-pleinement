<?php

use App\Models\Post;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('posts')
            ->whereNull('reading_time_minutes')
            ->select(['id', 'content'])
            ->chunkById(200, function ($posts) {
                foreach ($posts as $post) {
                    DB::table('posts')
                        ->where('id', $post->id)
                        ->update(['reading_time_minutes' => Post::computeReadingTimeMinutes((string) $post->content)]);
                }
            });
    }

    /**
     * Rien à défaire : reading_time_minutes redevient simplement calculable à
     * la volée si non renseigné.
     */
    public function down(): void {}
};
