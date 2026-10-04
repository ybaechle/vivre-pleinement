<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Support\LikeSearch;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class PostSearch extends Component
{
    use WithPagination;

    private const PER_PAGE = 9;

    #[Url(as: 'q')]
    #[Validate('string|max:100')]
    public string $search = '';

    #[Url]
    #[Validate('string|max:100')]
    public string $category = '';

    #[Url]
    #[Validate('string|max:100')]
    public string $tag = '';

    #[Url]
    #[Validate('in:recent,oldest')]
    public string $sort = 'recent';

    /**
     * Tronque défensivement les valeurs venues de l'URL : #[Validate] ne
     * s'applique qu'aux mises à jour ultérieures via wire:model, pas à
     * l'hydratation initiale depuis la query string.
     */
    public function mount(): void
    {
        $this->search = mb_substr($this->search, 0, 100);
        $this->category = mb_substr($this->category, 0, 100);
        $this->tag = mb_substr($this->tag, 0, 100);

        if (! in_array($this->sort, ['recent', 'oldest'], true)) {
            $this->sort = 'recent';
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedTag(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    /**
     * Retire un filtre actif (recherche, catégorie ou tag) via les chips.
     */
    public function removeFilter(string $key): void
    {
        if (in_array($key, ['search', 'category', 'tag'], true)) {
            $this->{$key} = '';
            $this->resetPage();
        }
    }

    public function clearAll(): void
    {
        $this->search = '';
        $this->category = '';
        $this->tag = '';
        $this->resetPage();
    }

    /**
     * Un filtre (recherche, catégorie ou tag) est-il actif ?
     */
    public function hasFilters(): bool
    {
        return trim($this->search) !== '' || $this->category !== '' || $this->tag !== '';
    }

    /**
     * Article à la une : uniquement sur la vue vierge (aucun filtre, tri par
     * défaut), en première page. Reproduit la logique du PostController.
     */
    #[Computed]
    public function featured(): ?Post
    {
        if (! $this->isUnfilteredRecent() || $this->getPage() > 1) {
            return null;
        }

        return Post::query()
            ->published()
            ->with(['categories', 'tags', 'media'])
            ->orderByDesc('published_at')
            ->first();
    }

    public function render(): View
    {
        $featured = $this->featured;

        /**
         * L'article à la une est retiré de la liste sur toutes les pages de la
         * vue vierge, pas seulement la première : sinon la pagination décale et
         * un même article apparaît en fin de page 1 et en tête de page 2.
         */
        $featuredId = $this->isUnfilteredRecent()
            ? ($featured?->id ?? Post::query()->published()->orderByDesc('published_at')->value('id'))
            : null;

        $posts = Post::query()
            ->published()
            ->with(['categories', 'tags', 'media'])
            ->when($featuredId, fn (Builder $q) => $q->whereKeyNot($featuredId))
            ->when(trim($this->search) !== '', function (Builder $q): void {
                $like = LikeSearch::wrap(trim($this->search));

                $q->where(function (Builder $sub) use ($like): void {
                    $sub->where('title', 'like', $like)
                        ->orWhere('excerpt', 'like', $like)
                        ->orWhere('content', 'like', $like);
                });
            })
            ->when($this->category !== '', fn (Builder $q) => $q->whereHas(
                'categories',
                fn (Builder $cq) => $cq->where('slug', $this->category),
            ))
            ->when($this->tag !== '', fn (Builder $q) => $q->whereHas(
                'tags',
                fn (Builder $tq) => $tq->where('slug', $this->tag),
            ))
            ->orderBy('published_at', $this->sort === 'oldest' ? 'asc' : 'desc')
            ->paginate(self::PER_PAGE, [
                'id', 'slug', 'title', 'excerpt', 'published_at', 'updated_at', 'reading_time_minutes',
            ]);

        return view('livewire.post-search', [
            'posts' => $posts,
            'featured' => $featured,
            'chips' => $this->chips(),
            'sidebarCategories' => $this->sidebarCategories(),
            'popularTags' => $this->popularTags(),
        ]);
    }

    private function isUnfilteredRecent(): bool
    {
        return ! $this->hasFilters() && $this->sort === 'recent';
    }

    /**
     * Catégories avec compteur d'articles publiés (sidebar).
     *
     * @return EloquentCollection<int, Category>
     */
    private function sidebarCategories(): EloquentCollection
    {
        return Category::query()
            ->withCount(['posts' => fn (Builder $q) => $q->published()])
            ->orderBy('name')
            ->get();
    }

    /**
     * Tags populaires (sidebar).
     *
     * @return Collection<int, Tag>
     */
    private function popularTags(): Collection
    {
        return Tag::query()
            ->withCount(['posts' => fn (Builder $q) => $q->published()])
            ->orderByDesc('posts_count')
            ->limit(20)
            ->get()
            ->filter(fn (Tag $t) => $t->posts_count > 0)
            ->values();
    }

    /**
     * Chips des filtres actifs, pour affichage et retrait.
     *
     * @return list<array{label: string, key: string}>
     */
    private function chips(): array
    {
        $chips = [];

        if (trim($this->search) !== '') {
            $chips[] = ['label' => '« '.$this->search.' »', 'key' => 'search'];
        }

        if ($this->category !== '') {
            $name = Category::query()->where('slug', $this->category)->value('name');
            if ($name) {
                $chips[] = ['label' => $name, 'key' => 'category'];
            }
        }

        if ($this->tag !== '') {
            $name = Tag::query()->where('slug', $this->tag)->value('name');
            if ($name) {
                $chips[] = ['label' => '#'.$name, 'key' => 'tag'];
            }
        }

        return $chips;
    }
}
