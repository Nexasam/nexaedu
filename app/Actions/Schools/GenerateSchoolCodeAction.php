<?php

namespace App\Actions\Schools;

use App\Models\School;

class GenerateSchoolCodeAction
{
    public function handle(): string
    {
        do {
            $code = random_int(10_000_000, 99_999_999);
        } while (School::query()->where('code', (string) $code)->exists());

        return (string) $code;
    }
}
