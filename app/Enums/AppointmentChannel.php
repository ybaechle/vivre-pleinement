<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum AppointmentChannel: string implements HasColor, HasIcon, HasLabel
{
    case Phone = 'phone';
    case Video = 'video';

    public function getLabel(): string
    {
        return match ($this) {
            self::Phone => 'Par téléphone',
            self::Video => 'En visioconférence',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Phone => 'info',
            self::Video => 'success',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Phone => Heroicon::OutlinedPhone,
            self::Video => Heroicon::OutlinedVideoCamera,
        };
    }
}
