<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class StudentAccountService
{
    public function username(string $firstName, string $lastName): string
    {
        $base = Str::lower(Str::ascii($firstName.'.'.$lastName));
        $base = preg_replace('/[^a-z0-9.]/', '', str_replace(' ', '', $base)) ?: 'student';
        $candidate = $base;
        $suffix = 2;

        while (User::where('username', $candidate)->exists()) {
            $candidate = $base.$suffix++;
        }

        return $candidate;
    }

    public function temporaryPassword(): string
    {
        return Str::password(12, symbols: false);
    }
}
