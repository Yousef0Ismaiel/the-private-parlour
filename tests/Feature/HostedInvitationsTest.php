<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Artisan;

class HostedInvitationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_preparing_a_code_is_offline_and_importing_it_is_idempotent(): void
    {
        $name = 'host-tester';
        $this->assertSame(0, Artisan::call('member:invite', ['username' => $name, '--prepare' => true]));
        $output = Artisan::output();
        $this->assertSame(1, preg_match('/\b[A-F0-9]{4}(?:-[A-F0-9]{4}){7}\b/', $output, $m));
        $code = $m[0];
        $digest = hash('sha256', str_replace('-', '', $code));
        $this->assertStringContainsString($digest, $output);
        $this->assertDatabaseCount('users', 0);

        $var = 'PARLOUR_TEST_DIGEST';
        $args = ['username' => $name, '--from-env' => $var];
        putenv($var.'='.$digest);
        try {
            $this->assertSame(0, Artisan::call('member:invite', $args));
            $this->assertStringNotContainsString($code, Artisan::output());
            $this->assertStringNotContainsString($digest, Artisan::output());
            $deadline = User::firstOrFail()->invite_expires_at;
            $this->travel(1)->hours();
            $this->assertSame(0, Artisan::call('member:invite', $args));
            $this->assertEquals($deadline, User::firstOrFail()->invite_expires_at);
            $this->assertDatabaseCount('users', 1);
            $this->post('/join', ['invitation' => $code])->assertRedirect('/pair');
            $this->setUpNewDigest($var, $args, $digest);
            $this->get('/pair')->assertRedirect('/join');

            User::query()->update(['password' => 'stored-hash', 'invite_hash' => null, 'invite_expires_at' => null]);
            $this->assertSame(0, Artisan::call('member:invite', $args));
            $this->assertNull(User::firstOrFail()->invite_hash);
        } finally {
            putenv($var);
        }
    }

    private function setUpNewDigest(string $var, array $args, string $oldDigest): void
    {
        putenv($var.'=wrong-format');
        $this->assertSame(1, Artisan::call('member:invite', $args));
        $this->assertSame($oldDigest, User::firstOrFail()->invite_hash);
        $newDigest = hash('sha256', strtoupper(bin2hex(random_bytes(16))));
        putenv($var.'='.$newDigest);
        $this->assertSame(0, Artisan::call('member:invite', $args));
        $this->assertSame($newDigest, User::firstOrFail()->invite_hash);
    }
}
