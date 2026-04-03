<?php

namespace Database\Factories;

use App\Models\MedicalFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicalFile>
 */
class MedicalFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $kind = $this->faker->randomElement(['image', 'pdf', 'report', 'other']);
        $original = match ($kind) {
            'image' => $this->faker->randomElement(['scan.png', 'photo.jpg', 'xray.webp']),
            'pdf' => $this->faker->randomElement(['report.pdf', 'lab-results.pdf']),
            'report' => $this->faker->randomElement(['note.txt', 'summary.txt']),
            default => $this->faker->randomElement(['file.bin', 'attachment.dat']),
        };

        $mime = match ($kind) {
            'image' => $this->faker->randomElement(['image/png', 'image/jpeg', 'image/webp']),
            'pdf' => 'application/pdf',
            'report' => 'text/plain',
            default => 'application/octet-stream',
        };

        return [
            'disk' => 'local',
            'path' => 'seed/'.now()->format('Ymd').'/'.$this->faker->uuid.'-'.$original,
            'original_name' => $original,
            'mime_type' => $mime,
            'size_bytes' => $this->faker->numberBetween(5000, 2_500_000),
            'file_kind' => $kind,
        ];
    }
}
