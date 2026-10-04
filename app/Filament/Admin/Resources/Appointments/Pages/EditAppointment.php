<?php

namespace App\Filament\Admin\Resources\Appointments\Pages;

use App\Filament\Admin\Resources\Appointments\AppointmentResource;
use App\Services\AppointmentLifecycleService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->wasChanged('status')) {
            app(AppointmentLifecycleService::class)->applyStatus($this->record);
        }
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Rendez-vous enregistré')
            ->body('Vos modifications ont bien été sauvegardées.');
    }
}
