@if (app()->environment('local'))
    <div class="qaari-demo-login">
        <p class="qaari-demo-login__hint">Local development</p>
        <button type="button" class="qaari-demo-login__btn" wire:click="fillDemoCredentials">
            Use demo credentials
        </button>
    </div>
@endif
