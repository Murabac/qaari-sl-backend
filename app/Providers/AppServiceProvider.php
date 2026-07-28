<?php

namespace App\Providers;

use App\Livewire\GenerateSignedUploadUrl;
use Illuminate\Support\ServiceProvider;
use Livewire\Facades\GenerateSignedUploadUrlFacade;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // R2 rejects x-amz-acl on browser uploads; omit ACL from presigned URLs.
        GenerateSignedUploadUrlFacade::swap(new GenerateSignedUploadUrl);
    }
}
