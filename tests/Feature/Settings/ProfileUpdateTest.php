<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('profile.edit'));

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_cannot_delete_their_account_while_deletion_is_disabled()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ]);

        $response->assertForbidden();
        $this->assertAuthenticatedAs($user);
        $this->assertModelExists($user);
    }


    public function test_locale_and_timezone_can_be_updated_with_valid_values(): void
    {
        $user = User::factory()->create([
            'locale' => 'hr-HR',
            'timezone' => 'Europe/Zagreb',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'locale' => 'en-GB',
                'timezone' => 'UTC',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('en-GB', $user->locale);
        $this->assertSame('UTC', $user->timezone);
    }

    public function test_locale_and_timezone_validation_fails_for_invalid_or_null_values(): void
    {
        $user = User::factory()->create([
            'locale' => 'hr-HR',
            'timezone' => 'Europe/Zagreb',
        ]);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'locale' => 'xx-INVALID',
                'timezone' => 'Mars/Olympus_Mons',
            ])
            ->assertSessionHasErrors(['locale', 'timezone']);

        $user->refresh();
        $this->assertSame('hr-HR', $user->locale);
        $this->assertSame('Europe/Zagreb', $user->timezone);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'locale' => null,
                'timezone' => null,
            ])
            ->assertSessionHasErrors(['locale', 'timezone']);

        $user->refresh();
        $this->assertSame('hr-HR', $user->locale);
        $this->assertSame('Europe/Zagreb', $user->timezone);
    }

    public function test_profile_update_ignores_protected_attributes(): void
    {
        $originalVerifiedAt = now()->subDays(5)->startOfSecond();

        $user = User::factory()->create([
            'password' => Hash::make('OriginalPassword123!'),
            'email_verified_at' => $originalVerifiedAt,
            'locale' => 'hr-HR',
            'timezone' => 'Europe/Zagreb',
        ]);

        $originalPasswordHash = $user->password;

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'NewName',
                'email' => $user->email,
                'locale' => 'en-GB',
                'timezone' => 'UTC',
                'password' => 'Password123!',
                'email_verified_at' => now()->addYear(),
            ]);

        $response->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame('NewName', $user->name);
        $this->assertSame($originalPasswordHash, $user->password);
        $this->assertTrue($user->email_verified_at->equalTo($originalVerifiedAt));
    }

    public function test_profile_saves_email_in_lowercase(): void
    {
        $user = User::factory()->create([
            'email' => 'original@example.com',
        ]);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => 'New@Example.com',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertSame('new@example.com', $user->refresh()->email);
    }

    public function test_profile_rejects_another_users_email_with_different_case(): void
    {
        User::factory()->create([
            'email' => 'taken@example.com',
        ]);

        $user = User::factory()->create([
            'email' => 'original@example.com',
        ]);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => 'Taken@Example.com',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame('original@example.com', $user->refresh()->email);
    }

    public function test_guest_cannot_update_a_profile()
    {
        $user = User::factory()->create([
            'name' => 'initialName',
            'email' => 'initial@email.com',
        ]);
        $response = $this->patch(route('profile.update'), [
            'name' => 'TestName',
            'email' => 'Taken@Example.com',
        ]);
        $user->refresh();

        $this->assertEquals('initialName', $user->name);
        $this->assertEquals('initial@email.com', $user->email);
        $response->assertRedirect(route('login'));
    }

    public function test_profile_update_rejects_invalid_name_and_email()
    {
        $user = User::factory()->create([
            'name' => 'initialName',
            'email' => 'initial@email.com',
        ]);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => '',
            'email' => 'invalid-email',
        ]);

        $user->refresh();

        $response->assertSessionHasErrors(['name', 'email']);
        $this->assertEquals('initialName', $user->name);
        $this->assertEquals('initial@email.com', $user->email);
    }
}
