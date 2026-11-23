<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\Election;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Candidate> */
class CandidateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'election_id' => Election::factory(),
            'number' => 1,
            'chair_name' => fake()->name(),
            'vice_name' => fake()->name(),
            'tagline' => 'Bersama membangun OSIS yang hebat',
            'vision' => 'Menjadikan OSIS wadah aspirasi seluruh siswa.',
            'missions' => ['Kotak aspirasi tiap bulan.', 'Lomba antarkelas tiap semester.'],
            'photo_path' => 'candidates/contoh.webp',
            'accent' => 'paslon-1',
        ];
    }

    /** Nomor urut + warna aksen yang serasi (paslon-n). */
    public function number(int $number): static
    {
        return $this->state(['number' => $number, 'accent' => "paslon-{$number}"]);
    }
}
