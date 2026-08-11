<?php

namespace App\Filament\Concerns;

/**
 * Coolify returns 500 when Livewire remorphs Filament edit pages that contain
 * private R2 FileUpload fields — even after the DB write already succeeded.
 *
 * Call skipRender() only from afterSave so validation / Halt paths still remorph.
 * Do NOT call fillForm() here — mutating state then remorphing/skipping has caused
 * checksum and FileUpload hydrate failures on production.
 */
trait SkipsRenderAfterSuccessfulSave
{
    protected function afterSave(): void
    {
        $this->skipRender();
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }
}
