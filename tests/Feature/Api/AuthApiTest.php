<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCatalogData;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use CreatesCatalogData;
    use RefreshDatabase;

    public function test_register_returns_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.email', 'new@example.com')
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user']]);

        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
    }

    public function test_login_returns_token(): void
    {
        $this->makeUser(['email' => 'login@example.com']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_me_requires_auth(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_me_and_logout(): void
    {
        $user = $this->makeUser(['email' => 'me@example.com']);
        $tokenResult = $user->createToken('api');
        $plainText = $tokenResult->plainTextToken;

        $this->withToken($plainText)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'me@example.com');

        $this->withToken($plainText)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenResult->accessToken->id,
        ]);
    }

    public function test_invalid_token_is_rejected(): void
    {
        $this->withToken('1|this-token-is-not-real')
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }
}
