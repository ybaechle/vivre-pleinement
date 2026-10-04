<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum VideoEditorialState: string implements HasColor, HasIcon, HasLabel
{
    case Complete = 'complete';
    case MissingTranscript = 'missing_transcript';
    case ToEnrich = 'to_enrich';
    case ToDo = 'to_do';

    public function getLabel(): string
    {
        return match ($this) {
            self::Complete => 'Complet',
            self::MissingTranscript => 'Sans transcription',
            self::ToEnrich => 'À enrichir',
            self::ToDo => 'À traiter',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Complete => 'success',
            self::MissingTranscript, self::ToEnrich => 'warning',
            self::ToDo => 'danger',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Complete => Heroicon::CheckCircle,
            self::MissingTranscript, self::ToEnrich => Heroicon::PencilSquare,
            self::ToDo => Heroicon::ExclamationTriangle,
        };
    }
}
