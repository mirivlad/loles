<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserCredential;

class StudentCredentials
{
    // Avoid confusing 0, 1, I, l, O on printed sheets.
    private const ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    public function generate(int $length = 8): string
    {
        $password = '';
        $max = strlen(self::ALPHABET) - 1;

        for ($i = 0; $i < $length; $i++) {
            $password .= self::ALPHABET[random_int(0, $max)];
        }

        return $password;
    }

    public function issue(User $student, string $password, ?User $issuer = null): void
    {
        if (!$student->hasRole('Student')) {
            throw new \LogicException('Only student credentials can be retained for issue sheets.');
        }

        UserCredential::updateOrCreate(
            ['user_id' => $student->id],
            ['secret' => $password, 'issued_by' => $issuer?->id]
        );
    }

    public function clear(User $user): void
    {
        UserCredential::where('user_id', $user->id)->delete();
    }
}
