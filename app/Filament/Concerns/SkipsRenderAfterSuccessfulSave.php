<?php

namespace App\Filament\Concerns;

use Throwable;

/**
 * Coolify returns 500 when Livewire remorphs Filament edit pages that contain
 * private R2 FileUpload fields — even after the DB write already succeeded.
 *
 * Call skipRender() only from afterSave so validation / Halt paths still remorph.
 */
trait SkipsRenderAfterSuccessfulSave
{
    protected function afterSave(): void
    {
        try {
            // Refresh in-memory state (temp upload → stored R2 path) without HTML remorph.
            $this->fillForm();
        } catch (Throwable $e) {
            report($e);
        }

        $this->skipRender();
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }
}
