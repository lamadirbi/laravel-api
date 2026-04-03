<?php

namespace Database\Factories;

use App\Models\Consultation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Consultation>
 */
class ConsultationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $submittedAt = Carbon::instance($this->faker->dateTimeBetween('-30 days', 'now'));
        $status = $this->faker->randomElement(['pending', 'completed']);
        $hasResponse = $status === 'completed' || $this->faker->boolean(35);

        return [
            'question_text' => $this->faker->paragraphs($this->faker->numberBetween(1, 3), true),
            'status' => $status,
            'submitted_at' => $submittedAt,
            'responded_at' => $hasResponse ? $submittedAt->copy()->addHours($this->faker->numberBetween(2, 72)) : null,
            'physician_response' => $hasResponse ? $this->faker->paragraphs($this->faker->numberBetween(1, 2), true) : null,
        ];
    }
}
