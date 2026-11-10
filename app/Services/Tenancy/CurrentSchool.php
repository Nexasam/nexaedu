<?php

namespace App\Services\Tenancy;

use App\Models\School;

class CurrentSchool
{
    private ?School $school = null;

    public function set(?School $school): void
    {
        $this->school = $school;
    }

    public function get(): ?School
    {
        return $this->school;
    }

    public function id(): ?int
    {
        return $this->school?->id;
    }
}
