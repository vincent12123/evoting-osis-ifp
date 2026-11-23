<?php

namespace Database\Factories;

use App\Models\Election;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Election> */
class ElectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Pemilihan Ketua & Wakil Ketua OSIS',
            'school_year' => '2026/2027',
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function status(string $status): static
    {
        return $this->state(['status' => $status]);
    }
}
