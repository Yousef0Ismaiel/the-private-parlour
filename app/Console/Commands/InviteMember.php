<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class InviteMember extends Command
{
    protected $signature = 'member:invite
        {username : 2–40 letters, digits, dots, underscores or hyphens}
        {--prepare : Print a personal invitation and the digest to enter on the host}
        {--from-env= : Apply the SHA-256 digest in this environment variable on startup}';
    protected $description = 'Issue a one-use invitation for a new member, valid for 24 hours';

    public function handle(): int
    {
        $name = strtolower(trim($this->argument('username')));
        if (! preg_match('/\A[a-z0-9][a-z0-9._-]{1,39}\z/', $name)) {
            $this->error('Choose a username of 2–40 characters, starting with a letter or number.');
            return self::FAILURE;
        }

        $envName = $this->option('from-env');
        if ($envName !== null) {
            $digest = getenv($envName);
            if ($this->option('prepare') || ! is_string($digest) || ! preg_match('/\A[a-f0-9]{64}\z/', $digest)) {
                $this->error('Supply a valid lowercase SHA-256 invitation digest.');
                return self::FAILURE;
            }
            // An unchanged or already redeemed invitation is an expected startup state.
            $this->saveInvitation($name, $digest);
            return self::SUCCESS;
        }

        $code = strtoupper(bin2hex(random_bytes(16)));
        $digest = hash('sha256', $code);
        if (! $this->option('prepare') && ! $this->saveInvitation($name, $digest)) {
            $this->error('That member has already enrolled.');
            return self::FAILURE;
        }
        $this->info("Invitation for {$name} (valid for 24 hours once issued on the host)");
        $this->line(implode('-', str_split($code, 4)));
        $this->line('Give the code privately to this member. Choose “Use an invitation” on the sign-in page.');
        if ($this->option('prepare')) {
            $this->line("Invitation digest for the host: {$digest}");
        }
        return self::SUCCESS;
    }

    private function saveInvitation(string $name, string $digest): bool
    {
        $member = User::query()->firstOrCreate(['username' => $name]);
        // A restart cannot reopen a claimed invitation or extend its original deadline.
        return (bool) User::whereKey($member->id)->whereNull('password')
            ->where(fn ($query) => $query->whereNull('invite_hash')->orWhere('invite_hash', '<>', $digest))
            ->update(['invite_hash' => $digest, 'invite_expires_at' => now()->addDay()]);
    }
}
