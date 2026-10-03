<?php

namespace App\Filament\Admin\Resources\Comments\Schemas;

use App\Enums\CommentStatus;
use App\Models\Comment;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CommentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Commentaire')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->columns(2)
                ->schema([
                    Select::make('post_id')
                        ->label('Article')
                        ->relationship('post', 'title')
                        ->searchable()
                        ->required()
                        ->columnSpanFull(),

                    TextInput::make('author_name')
                        ->label('Auteur')
                        ->required(),

                    TextInput::make('author_email')
                        ->label('Email')
                        ->email(),

                    Textarea::make('content')
                        ->label('Contenu')
                        ->required()
                        ->rows(5)
                        ->columnSpanFull(),

                    Select::make('status')
                        ->label('Statut')
                        ->options(CommentStatus::class)
                        ->default(CommentStatus::Approved)
                        ->required(),

                    DateTimePicker::make('posted_at')
                        ->label('Posté le')
                        ->seconds(false),
                ]),

            Section::make('Réponse à')
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->collapsed()
                ->collapsible()
                ->schema([
                    Select::make('parent_id')
                        ->label('Commentaire parent')
                        ->relationship(
                            'parent',
                            'author_name',
                            fn (Builder $query, Get $get) => $query->where('post_id', $get('post_id')),
                        )
                        ->getOptionLabelFromRecordUsing(fn (Comment $comment): string => $comment->author_name.' : '.Str::limit($comment->content, 60))
                        ->searchable(['author_name', 'content']),
                ]),

            Section::make('Détails techniques')
                ->icon(Heroicon::OutlinedWrenchScrewdriver)
                ->columns(2)
                ->collapsed()
                ->collapsible()
                ->schema([
                    TextInput::make('author_url')
                        ->label('URL auteur')
                        ->url(),

                    TextInput::make('author_ip')
                        ->label('IP')
                        ->disabled(),
                ]),
        ]);
    }
}
