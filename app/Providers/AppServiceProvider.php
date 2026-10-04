<?php

namespace App\Providers;

use App\Models\Course;
use App\Observers\MediaObserver;
use App\Support\FontPreloads;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Media::observe(MediaObserver::class);

        Model::shouldBeStrict(! $this->app->isProduction());

        /**
         * La navbar et le footer habillent aussi les pages d'erreur : quand la
         * base est tombée, l'échec est signalé (rescue) et le lien vers les
         * formations masqué, au lieu de faire échouer la page d'erreur elle-même.
         */
        View::composer(['layouts.partials.navbar', 'home.sections.footer'], function (ViewContract $view): void {
            $view->with('hasCourses', rescue(fn (): bool => Course::hasPublished(), false));
        });

        Vite::usePreloadTagAttributes(function (string $src, string $url) {
            if ($src === 'fonts' && ! FontPreloads::shouldPreload($url)) {
                return false;
            }

            return [];
        });
    }
}
