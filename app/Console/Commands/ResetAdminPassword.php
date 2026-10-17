<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ResetAdminPassword extends Command
{
    protected $signature = 'admin:reset-password
                            {email : Email of the account}
                            {password? : New password (prompted if omitted)}
                            {--deactivate-test-accounts : Also disable leftover test accounts}';

    protected $description = 'Set a Laravel-compatible bcrypt password for an account';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        $user = DB::table('users')->where('email', $email)->first();
        if (! $user) {
            $this->components->error("No account found for {$email}");

            return self::FAILURE;
        }

        $password = $this->argument('password');
        if (! $password) {
            $password = $this->secret('New password');
        }

        if (strlen((string) $password) < 8) {
            $this->components->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        $hash = Hash::make($password);

        DB::table('users')
            ->where('email', $email)
            ->update([
                'password'   => $hash,
                'role'       => 'ADMIN',
                'isActive'   => 1,
                'updated_at' => now(),
            ]);

        $this->components->info("Password updated for {$email}");
        $this->line('  hash prefix: ' . substr($hash, 0, 7));

        if ($this->option('deactivate-test-accounts')) {
            $affected = DB::table('users')
                ->where('email', 'like', '%test%')
                ->orWhere('email', 'like', '%audit%')
                ->update(['isActive' => 0, 'updated_at' => now()]);

            $this->components->info("Deactivated {$affected} leftover test account(s)");
        }

        return self::SUCCESS;
    }
}
