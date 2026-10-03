<?php

namespace App\Filament\Admin\Resources\Redirects\Schemas;

use App\Http\Middleware\HandleRedirects;
use App\Models\Redirect;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RedirectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('from_path')
                ->label('URL source')
                ->required()
                ->rule(fn (?Redirect $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                    $exists = Redirect::query()
                        ->where('from_path', Redirect::normalizePath((string) $value))
                        ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                        ->exists();

                    if ($exists) {
                        $fail('Une redirection existe déjà pour cette URL source.');
                    }
                })
                ->placeholder('/ancien-article')
                ->prefix(url('/'))
                ->columnSpanFull(),

            TextInput::make('to_path')
                ->label('URL cible')
                ->required()
                ->placeholder('/nouveau-article')
                ->prefix(url('/'))
                ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                    if (HandleRedirects::resolveTarget((string) $value) === null) {
                        $fail('Indiquez un chemin du site ou une adresse en http(s).');
                    }
                })
                ->columnSpanFull(),

            Select::make('status_code')
                ->label('Code HTTP')
                ->options([
                    301 => '301 – Permanent',
                    302 => '302 – Temporaire',
                ])
                ->default(301)
                ->required(),
        ]);
    }
}
