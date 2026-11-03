<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Str;

/**
 * Lower-case the "email" input before validation. Accounts keep their e-mail in lower case (see User::email()), so
 * the uniqueness rule and the sign-in look it up the same way whatever case it was typed in.
 */
trait NormalizesEmail
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $email = $this->input('email');
        if (is_string($email)) {
            $this->merge(['email' => Str::lower($email)]);
        }
    }
}
