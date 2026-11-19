<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class MemberAccessTest extends TestCase
{
    use RefreshDatabase;

    private const PASSPHRASE = 'a testing-only phrase for the parlour';

    private function member(): User
    {
        $member = new User(['username' => 'member']);
        $member->password = Hash::make(self::PASSPHRASE);
        $member->two_factor_secret = app(Google2FA::class)->generateSecretKey();
        $member->save();
        return $member;
    }

    private function invitation(string $username = 'friend'): string
    {
        $this->assertSame(0, Artisan::call('member:invite', ['username' => $username]));
        $this->assertSame(1, preg_match('/\b[A-F0-9]{4}(?:-[A-F0-9]{4}){7}\b/', Artisan::output(), $m));
        return $m[0];
    }

    public function test_guests_see_only_the_neutral_entrance_even_at_private_asset_urls(): void
    {
        foreach (['/', '/edition/cover.webp', '/edition/print.css'] as $path) {
            $this->get($path)->assertRedirect('/login')->assertDontSee('Dildos');
            $this->head($path)->assertRedirect('/login');
        }
        foreach (['/login', '/join'] as $path) {
            $response = $this->get($path)->assertOk()->assertDontSee('Dildos')
                ->assertDontSee('/edition/')->assertDontSee('Clutch your');
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        }
        $this->get('/pair')->assertRedirect('/join');
        $this->post('/verify', ['code' => '123456'])->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_both_factors_are_required_and_codes_cannot_be_spent_again(): void
    {
        $member = $this->member();
        $creds = ['username' => $member->username, 'password' => self::PASSPHRASE];
        $code = app(Google2FA::class)->getCurrentOtp($member->two_factor_secret);

        $this->from('/login')->post('/login', ['username' => 'member', 'password' => 'incorrect'])
            ->assertSessionHasErrors('entry');
        $this->get('/verify')->assertRedirect('/login');
        $this->post('/login', $creds)->assertRedirect('/verify');
        $this->assertGuest();
        $this->get('/')->assertRedirect('/login');
        $this->get('/edition/cover.webp')->assertRedirect('/login');
        $this->post('/verify', ['code' => '12'])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post('/verify', ['code' => $code])->assertRedirect('/');
        $this->assertAuthenticatedAs($member);
        $response = $this->get('/')->assertOk()->assertSee('Dildos');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->get('/edition/cover.webp')->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->get('/edition/print.css')->assertOk();

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/edition/cover.webp')->assertRedirect('/login');
        $this->post('/login', $creds)->assertRedirect('/verify');
        $this->from('/verify')->post('/verify', ['code' => $code])->assertSessionHasErrors('entry');
        $this->assertGuest();
    }

    public function test_an_invitation_pairs_one_phone_and_requires_a_code_to_finish(): void
    {
        $invite = $this->invitation();
        $this->post('/join', ['invitation' => $invite])->assertRedirect('/pair');
        $response = $this->get('/pair')->assertOk()->assertSee('data:image/svg+xml;base64,', false);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $secret = session('entry.secret');
        $creds = ['password' => self::PASSPHRASE, 'password_confirmation' => self::PASSPHRASE];
        $this->post('/pair', $creds)->assertSessionHasErrors('code');
        $this->get('/')->assertRedirect('/login');
        $code = app(Google2FA::class)->getCurrentOtp($secret);
        $this->post('/pair', $creds + ['code' => $code])->assertRedirect('/');
        $this->get('/')->assertOk()->assertSee('Dildos');

        $stored = DB::table('users')->where('username', 'friend')->first();
        $this->assertNotSame($secret, $stored->two_factor_secret);
        $this->assertTrue(Hash::check(self::PASSPHRASE, $stored->password));
        $this->assertNull($stored->invite_hash);
        $this->post('/logout');
        $this->post('/join', ['invitation' => $invite])->assertSessionHasErrors('entry');
        $this->get('/pair')->assertRedirect('/join');
        $this->assertSame(1, Artisan::call('member:invite', ['username' => 'friend']));
    }

    public function test_reissuing_an_invite_revokes_the_prior_pairing_session(): void
    {
        $first = $this->invitation();
        $this->post('/join', ['invitation' => $first])->assertRedirect('/pair');
        $second = $this->invitation();
        $this->assertNotSame($first, $second);
        $this->get('/pair')->assertRedirect('/join');
        $this->post('/join', ['invitation' => $first])->assertSessionHasErrors('entry');
        $this->post('/join', ['invitation' => $second])->assertRedirect('/pair');
        $this->travel(16)->minutes();
        $this->get('/pair')->assertRedirect('/join');
        $this->travel(25)->hours();
        $this->post('/join', ['invitation' => $second])->assertSessionHasErrors('entry');
    }

    public function test_a_password_challenge_expires(): void
    {
        $member = $this->member();
        $this->post('/login', ['username' => 'member', 'password' => self::PASSPHRASE])->assertRedirect('/verify');
        $this->travel(6)->minutes();
        $this->post('/verify', ['code' => app(Google2FA::class)->getCurrentOtp($member->two_factor_secret)])
            ->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_ordinary_auth_sessions_without_the_second_factor_cannot_enter(): void
    {
        $this->actingAs($this->member())->get('/')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_the_code_challenge_throttles_repeated_attempts(): void
    {
        $this->member();
        $this->post('/login', ['username' => 'member', 'password' => self::PASSPHRASE]);
        for ($i = 0; $i < 5; $i++) {
            $this->from('/verify')->post('/verify', ['code' => 'bad'])->assertRedirect('/verify');
        }
        $this->post('/verify', ['code' => 'bad'])->assertStatus(429);
        $this->assertGuest();
    }
}
