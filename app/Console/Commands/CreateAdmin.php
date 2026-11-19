<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'loles:create-admin';
    protected $description = 'Interactively create a LoLeS administrator without demo credentials';

    public function handle(): int
    {
        if (!$this->input->isInteractive()) {
            $this->error('Run this command in an interactive terminal (Portainer Console or docker compose exec).');
            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Administrator name'));
        $email = trim((string) $this->ask('Administrator email'));

        $validator = Validator::make(['name' => $name, 'email' => $email], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());
            return self::FAILURE;
        }

        $password = (string) $this->secret('Password (at least 12 characters)');
        if (mb_strlen($password) < 12) {
            $this->error('Password must be at least 12 characters long.');
            return self::FAILURE;
        }
        if ($password !== (string) $this->secret('Repeat password')) {
            $this->error('Passwords do not match.');
            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole('Administrator');

        $this->info("Administrator created: {$email}");
        return self::SUCCESS;
    }
}
