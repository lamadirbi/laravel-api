<?php

namespace Database\Factories;

use App\Models\MedicalProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicalProfile>
 */
class MedicalProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'height_cm' => $this->faker->optional(0.9)->numberBetween(140, 200),
            'weight_kg' => $this->faker->optional(0.9)->numberBetween(45, 120),
            'chronic_diseases' => $this->faker->optional(0.8)->sentence(6),
            'medical_history' => $this->faker->optional(0.8)->paragraph(),
            'allergies' => $this->faker->optional(0.7)->sentence(4),
            'current_medications' => $this->faker->optional(0.7)->sentence(5),
        ];
    }
}
