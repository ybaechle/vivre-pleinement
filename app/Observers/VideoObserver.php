<?php

namespace App\Observers;

use App\Http\Controllers\SitemapController;
use App\Models\Video;
use App\Support\IndexNow;
use App\Support\VideoArticleMatcher;
use Illuminate\Support\Facades\Cache;

class VideoObserver
{
    /**
     * Rafraîchis à chaque synchronisation horaire sans changer la page :
     * ils ne justifient ni une notification IndexNow ni une purge des caches.
     */
    private const SYNC_ONLY_ATTRIBUTES = ['view_count', 'like_count', 'synced_at', 'updated_at'];

    public function created(Video $video): void
    {
        $this->contentChanged($video);
    }

    public function updated(Video $video): void
    {
        if (array_diff(array_keys($video->getChanges()), self::SYNC_ONLY_ATTRIBUTES) !== []) {
            $this->contentChanged($video);
        }
    }

    public function deleted(Video $video): void
    {
        $this->flushCaches();
    }

    public function restored(Video $video): void
    {
        $this->flushCaches();
    }

    public function forceDeleted(Video $video): void
    {
        $this->flushCaches();
    }

    private function contentChanged(Video $video): void
    {
        $this->flushCaches();

        if (Video::query()->indexable()->whereKey($video->getKey())->exists()) {
            IndexNow::ping(route('videos.show', $video->slug));
        }
    }

    private function flushCaches(): void
    {
        Cache::forget('sitemap.urls');
        Cache::forget(SitemapController::VIDEOS_CACHE_KEY);

        VideoArticleMatcher::flush();
    }
}
