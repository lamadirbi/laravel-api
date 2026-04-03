<?php

namespace Database\Factories;

use App\Models\PhysicianProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PhysicianProfile>
 */
class PhysicianProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'specialty' => $this->faker->randomElement([
                'طب الأسرة',
                'أمراض القلب',
                'الأطفال',
                'الجلدية',
                'العظام',
                'الأنف والأذن والحنجرة',
                'العيون',
                'النساء والتوليد',
                'الطب النفسي',
            ]),
            'certificate' => $this->faker->sentence(10),
        ];
    }
}
