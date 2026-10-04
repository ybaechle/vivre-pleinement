<?php

namespace App\Observers;

use App\Models\Category;
use App\Support\InternalLinking;
use Illuminate\Support\Facades\Cache;

class CategoryObserver
{
    public function saved(Category $category): void
    {
        $this->flushCaches();
    }

    public function deleted(Category $category): void
    {
        $this->flushCaches();
    }

    private function flushCaches(): void
    {
        Cache::forget('sitemap.urls');

        InternalLinking::flush();
    }
}
