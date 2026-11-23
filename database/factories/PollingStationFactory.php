<?php

namespace Database\Factories;

use App\Models\PollingStation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PollingStation> */
class PollingStationFactory extends Factory
{
    private static int $sequence = 0;

    public function definition(): array
    {
        return [
            'name' => 'TPS '.(++self::$sequence),
            'location' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
