@if (app()->environment('local'))
    <div class="qaari-demo-login">
        <p class="qaari-demo-login__hint">Quick local login</p>
        <div class="qaari-demo-login__actions">
            <button type="button" class="qaari-demo-login__btn" wire:click="loginAsSuperAdmin">
                Super Admin
            </button>
            <button type="button" class="qaari-demo-login__btn" wire:click="loginAsAdmin">
                Admin
            </button>
            <button type="button" class="qaari-demo-login__btn" wire:click="loginAsProduction">
                Production
            </button>
        </div>
    </div>
@endif
