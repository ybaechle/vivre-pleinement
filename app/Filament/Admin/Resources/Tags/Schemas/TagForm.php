<?php

namespace App\Filament\Admin\Resources\Tags\Schemas;

use App\Support\SlugFromTitle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nom')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(SlugFromTitle::onCreate()),

            TextInput::make('slug')
                ->required()
                ->unique(ignoreRecord: true),

            Textarea::make('description')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }
}
