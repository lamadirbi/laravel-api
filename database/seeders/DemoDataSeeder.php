<?php

namespace Database\Seeders;

use App\Models\Consultation;
use App\Models\MedicalFile;
use App\Models\MedicalProfile;
use App\Models\PhysicianProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // clean up seed files (safe even if missing)
        Storage::disk('local')->deleteDirectory('seed');

        // fixed accounts for easier login (updateOrCreate = آمن عند إعادة تشغيل السيدر)
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ]
        );

        $physicians = collect([
            ['name' => 'د. أحمد', 'email' => 'dr.ahmad@example.com'],
            ['name' => 'د. سارة', 'email' => 'dr.sara@example.com'],
            ['name' => 'د. محمد', 'email' => 'dr.mohammad@example.com'],
        ])->map(fn (array $row) => User::updateOrCreate(
            ['email' => $row['email']],
            [
                'name' => $row['name'],
                'password' => Hash::make('password'),
                'role' => User::ROLE_PHYSICIAN,
                'email_verified_at' => now(),
            ]
        ));

        $patients = collect([
            ['name' => 'مريم', 'email' => 'patient.maryam@example.com'],
            ['name' => 'علي', 'email' => 'patient.ali@example.com'],
            ['name' => 'نور', 'email' => 'patient.noor@example.com'],
            ['name' => 'يوسف', 'email' => 'patient.yousef@example.com'],
            ['name' => 'هبة', 'email' => 'patient.heba@example.com'],
        ])->map(fn (array $row) => User::updateOrCreate(
            ['email' => $row['email']],
            [
                'name' => $row['name'],
                'password' => Hash::make('password'),
                'role' => User::ROLE_PATIENT,
                'email_verified_at' => now(),
            ]
        ));

        // extra random users (بريد فريد صريح لتفادي تكرار faker مع بيانات قديمة)
        $patients = $patients->merge(collect(range(1, 12))->map(fn () => User::factory()->patient()->create([
            'email' => 'seed-patient-'.Str::uuid().'@example.test',
        ])));
        $physicians = $physicians->merge(collect(range(1, 5))->map(fn () => User::factory()->physician()->create([
            'email' => 'seed-physician-'.Str::uuid().'@example.test',
        ])));

        // medical profiles for patients
        foreach ($patients as $p) {
            MedicalProfile::firstOrCreate(
                ['user_id' => $p->id],
                MedicalProfile::factory()->make()->toArray()
            );
        }

        // physician profiles + certificate attachments (real files)
        foreach ($physicians as $doc) {
            $profile = PhysicianProfile::firstOrCreate(
                ['user_id' => $doc->id],
                PhysicianProfile::factory()->make(['user_id' => $doc->id])->toArray()
            );

            if (! $profile->wasRecentlyCreated) {
                continue;
            }

            $certCount = random_int(1, 3);
            $certFileIds = [];
            for ($i = 0; $i < $certCount; $i++) {
                $isPdf = $i === 0 ? (bool) random_int(0, 1) : (bool) random_int(0, 1);
                $original = $isPdf ? 'certificate-'.$i.'.pdf' : 'certificate-'.$i.'.png';
                $mime = $isPdf ? 'application/pdf' : 'image/png';
                $kind = $isPdf ? 'pdf' : 'image';

                $path = 'seed/certificates/'.$doc->id.'/'.Str::uuid().'-'.$original;
                $bytes = $isPdf ? $this->fakePdfBytes('Certificate') : $this->fakePngBytes();
                Storage::disk('local')->put($path, $bytes);

                $file = MedicalFile::create([
                    'owner_user_id' => $doc->id,
                    'uploaded_by_user_id' => $doc->id,
                    'consultation_id' => null,
                    'disk' => 'local',
                    'path' => $path,
                    'original_name' => $original,
                    'mime_type' => $mime,
                    'size_bytes' => strlen($bytes),
                    'file_kind' => $kind,
                ]);

                $certFileIds[] = (int) $file->id;
            }

            $profile->certificate_file_ids = $certFileIds;
            $profile->certificate_file_id = $certFileIds[0] ?? null;
            $profile->save();
        }

        // consultations: some unclaimed in queue (physician_id null), some claimed, mix of completed/pending
        $queueCount = 10;
        for ($i = 0; $i < $queueCount; $i++) {
            $patient = $patients->random();
            $c = Consultation::factory()->create([
                'patient_id' => $patient->id,
                'physician_id' => null,
                'status' => 'pending',
                'physician_response' => null,
                'responded_at' => null,
            ]);
            $this->seedConsultationAttachments($c, $patient);
        }

        $claimedCount = 25;
        for ($i = 0; $i < $claimedCount; $i++) {
            $patient = $patients->random();
            $physician = $physicians->random();
            $status = random_int(0, 1) ? 'pending' : 'completed';
            $hasResponse = $status === 'completed' || (bool) random_int(0, 1);

            $c = Consultation::factory()->create([
                'patient_id' => $patient->id,
                'physician_id' => $physician->id,
                'status' => $status,
                'physician_response' => $hasResponse ? fake()->paragraphs(random_int(1, 2), true) : null,
                'responded_at' => $hasResponse ? now()->subDays(random_int(0, 10)) : null,
            ]);
            $this->seedConsultationAttachments($c, $patient);
        }
    }

    private function seedConsultationAttachments(Consultation $consultation, User $patient): void
    {
        $count = random_int(0, 4);
        for ($i = 0; $i < $count; $i++) {
            $kind = fake()->randomElement(['image', 'pdf']);
            $original = $kind === 'pdf' ? ('attachment-'.$i.'.pdf') : ('attachment-'.$i.'.png');
            $mime = $kind === 'pdf' ? 'application/pdf' : 'image/png';
            $path = 'seed/consultations/'.$consultation->id.'/'.Str::uuid().'-'.$original;

            $bytes = $kind === 'pdf' ? $this->fakePdfBytes('Attachment') : $this->fakePngBytes();
            Storage::disk('local')->put($path, $bytes);

            MedicalFile::create([
                'owner_user_id' => $patient->id,
                'uploaded_by_user_id' => $patient->id,
                'consultation_id' => $consultation->id,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $original,
                'mime_type' => $mime,
                'size_bytes' => strlen($bytes),
                'file_kind' => $kind,
            ]);
        }
    }

    private function fakePngBytes(): string
    {
        // 1x1 transparent PNG
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMB/6X9qZ0AAAAASUVORK5CYII=',
            true
        ) ?: '';
    }

    private function fakePdfBytes(string $title): string
    {
        // Minimal PDF (good enough for preview/download)
        $safe = preg_replace('/[^a-zA-Z0-9 _-]/', '', $title) ?: 'Document';

        return "%PDF-1.4\n".
            "1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n".
            "2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj\n".
            "3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources<< /Font<< /F1 5 0 R >> >> >>endobj\n".
            "4 0 obj<< /Length 44 >>stream\n".
            "BT /F1 24 Tf 72 720 Td ({$safe}) Tj ET\n".
            "endstream endobj\n".
            "5 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>endobj\n".
            "xref\n0 6\n0000000000 65535 f \n".
            "trailer<< /Root 1 0 R /Size 6 >>\nstartxref\n0\n%%EOF\n";
    }
}
