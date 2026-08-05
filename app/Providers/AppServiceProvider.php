<?php

namespace App\Providers;

use App\Livewire\GenerateSignedUploadUrl;
use App\Models\Recitation;
use App\Models\Reciter;
use App\Models\User;
use App\Policies\RecitationPolicy;
use App\Policies\ReciterPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
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

        Gate::policy(Reciter::class, ReciterPolicy::class);
        Gate::policy(Recitation::class, RecitationPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}
