<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum AppointmentStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case NoShow = 'no_show';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Confirmed => 'Confirmé',
            self::Cancelled => 'Annulé',
            self::Completed => 'Terminé',
            self::NoShow => 'Absent',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'success',
            self::Cancelled => 'danger',
            self::Completed => 'gray',
            self::NoShow => 'danger',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Pending => Heroicon::OutlinedClock,
            self::Confirmed => Heroicon::OutlinedCheckCircle,
            self::Cancelled => Heroicon::OutlinedXCircle,
            self::Completed => Heroicon::OutlinedCheckBadge,
            self::NoShow => Heroicon::OutlinedUserMinus,
        };
    }

    /**
     * Indique si le rendez-vous est encore ouvert et peut être annulé ou
     * reprogrammé.
     */
    public function isCancellable(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed], true);
    }

    /**
     * Statuts qui occupent un créneau (et le bloquent à la réservation).
     *
     * @return array<int, self>
     */
    public static function blocking(): array
    {
        return [self::Pending, self::Confirmed, self::Completed];
    }
}
