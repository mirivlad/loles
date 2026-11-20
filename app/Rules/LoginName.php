<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * An LMS login is an identifier, not an email credential.
 * Allow short staff/student names and email-shaped names alike.
 */
class LoginName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('Укажите логин латиницей или в формате email.');
            return;
        }

        // Short usernames retain the existing strict alphabet.
        if (preg_match('/\A[a-z0-9._-]{3,64}\z/iD', $value)) {
            return;
        }

        // Staff may reuse their email address as a LOGIN, without requiring
        // that the separate contact email field be set or verified.
        if (strlen($value) <= 255 && filter_var($value, FILTER_VALIDATE_EMAIL) !== false) {
            return;
        }

        $fail('Логин: 3–64 символа (латиница, цифры, . _ -) или корректный адрес email.');
    }
}
