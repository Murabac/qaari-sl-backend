<?php

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCatalogData;
use Tests\TestCase;

class AccountDeletionPageTest extends TestCase
{
    use CreatesCatalogData;
    use RefreshDatabase;

    public function test_listener_can_delete_account_via_web_form(): void
    {
        $user = $this->makeUser(['email' => 'web-delete@example.com']);

        $response = $this->from(route('account-deletion'))
            ->delete(route('account-deletion.destroy'), [
                'email' => 'web-delete@example.com',
                'password' => 'password',
                'confirm' => '1',
            ]);

        $response->assertRedirect(route('account-deletion.done'));
        $this->assertDatabaseMissing('users', ['email' => 'web-delete@example.com']);

        $this->get(route('account-deletion.done'))
            ->assertOk();
    }

    public function test_logged_in_listener_is_logged_out_after_web_delete(): void
    {
        $user = $this->makeUser(['email' => 'web-delete-auth@example.com']);

        $response = $this->actingAs($user)
            ->from(route('account-deletion'))
            ->delete(route('account-deletion.destroy'), [
                'email' => 'web-delete-auth@example.com',
                'password' => 'password',
                'confirm' => '1',
            ]);

        $response->assertRedirect(route('account-deletion.done'));
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'web-delete-auth@example.com']);
    }
}
