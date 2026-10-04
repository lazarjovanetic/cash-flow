<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{
    protected $signature = 'app:korisnik {email} {--name=} {--password=}';

    protected $description = 'Kreira korisnika za prijavu ili menja lozinku postojećem';

    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->option('password') ?: $this->secret('Lozinka');
        if (strlen((string) $password) < 8) {
            $this->error('Lozinka mora imati bar 8 karaktera.');

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $email]);
        $user->name = $this->option('name') ?: ($user->name ?: strstr($email, '@', true));
        $user->password = Hash::make($password);
        $existed = $user->exists;
        $user->save();

        $this->info($existed ? "Lozinka promenjena za {$email}." : "Korisnik {$email} je kreiran.");

        return self::SUCCESS;
    }
}
