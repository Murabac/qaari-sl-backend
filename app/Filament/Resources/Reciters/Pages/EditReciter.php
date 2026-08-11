<?php

namespace App\Filament\Resources\Reciters\Pages;

use App\Filament\Resources\Reciters\ReciterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReciter extends EditRecord
{
    protected static string $resource = ReciterResource::class;

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        parent::save(shouldRedirect: false, shouldSendSavedNotification: $shouldSendSavedNotification);

        if ($shouldRedirect) {
            $this->redirect(static::getUrl(['record' => $this->getRecord()]), navigate: false);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successRedirectUrl(ReciterResource::getUrl('index')),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }
}
