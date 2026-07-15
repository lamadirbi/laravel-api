<?php

namespace Database\Seeders;

use App\Models\Consultation;
use App\Models\ConsultationMessage;
use App\Models\MedicalFile;
use App\Models\MedicalProfile;
use App\Models\PhysicianProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * بيانات عرض واضحة لغزة كير كونيكت.
 *
 * حسابات الدخول (كلمة المرور للجميع: password):
 * - admin@example.com                  مدير النظام
 * - patient.maryam@example.com         مريم عبدالله (مراجع)
 * - patient.ali@example.com            علي حسن (مراجع)
 * - patient.noor@example.com           نور خالد (مراجع)
 * - patient.yousef@example.com         يوسف سمير (مراجع)
 * - patient.heba@example.com           هبة نضال (مراجع)
 * - dr.ahmad@example.com               د. أحمد العكلوك (قلب) — موثّق
 * - dr.sara@example.com                د. سارة أبو شمالة (أطفال) — موثّق
 * - dr.mohammad@example.com            د. محمد البرغوثي (أسرة) — موثّق
 * - dr.pending.uat@example.com         د. لينا الداهودي (جلدية) — بانتظار التوثيق
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        Storage::disk('local')->deleteDirectory('seed');

        $admin = $this->user('آية منصور', 'admin@example.com', User::ROLE_ADMIN);

        $physicians = [
            $ahmad = $this->user('د. أحمد العكلوك', 'dr.ahmad@example.com', User::ROLE_PHYSICIAN),
            $sara = $this->user('د. سارة أبو شمالة', 'dr.sara@example.com', User::ROLE_PHYSICIAN),
            $mohammad = $this->user('د. محمد البرغوثي', 'dr.mohammad@example.com', User::ROLE_PHYSICIAN),
            $lina = $this->user('د. لينا الداهودي', 'dr.pending.uat@example.com', User::ROLE_PHYSICIAN),
        ];

        $patients = [
            $maryam = $this->user('مريم عبدالله', 'patient.maryam@example.com', User::ROLE_PATIENT),
            $ali = $this->user('علي حسن', 'patient.ali@example.com', User::ROLE_PATIENT),
            $noor = $this->user('نور خالد', 'patient.noor@example.com', User::ROLE_PATIENT),
            $yousef = $this->user('يوسف سمير', 'patient.yousef@example.com', User::ROLE_PATIENT),
            $heba = $this->user('هبة نضال', 'patient.heba@example.com', User::ROLE_PATIENT),
        ];

        $this->medicalProfile($maryam, [
            'height_cm' => 162,
            'weight_kg' => 68,
            'chronic_diseases' => 'ارتفاع ضغط الدم، سكري من النوع الثاني',
            'medical_history' => 'تشخيص السكري منذ 2019. مراقبة ضغط الدم في مركز الشفاء. لا عمليات جراحية سابقة.',
            'allergies' => 'حساسية من البنسلين',
            'current_medications' => 'ميتفورمين 500mg مرتين يومياً، أملوديبين 5mg صباحاً',
        ]);

        $this->medicalProfile($ali, [
            'height_cm' => 175,
            'weight_kg' => 82,
            'chronic_diseases' => 'ربو خفيف',
            'medical_history' => 'نوبات ربو موسمية خاصة في الربيع. يستخدم بخاخ فنتولين عند الحاجة.',
            'allergies' => 'غبار الطلع',
            'current_medications' => 'بخاخ فنتولين عند اللزوم',
        ]);

        $this->medicalProfile($noor, [
            'height_cm' => 158,
            'weight_kg' => 54,
            'chronic_diseases' => 'لا يوجد',
            'medical_history' => 'ولادة قيصرية عام 2022. متابعة ما بعد الولادة مكتملة.',
            'allergies' => 'لا توجد حساسية معروفة',
            'current_medications' => 'حديد 1 قرص يومياً، فيتامين د 1000 وحدة يومياً',
        ]);

        $this->medicalProfile($yousef, [
            'height_cm' => 180,
            'weight_kg' => 95,
            'chronic_diseases' => 'سمنة، آلام أسفل الظهر مزمنة',
            'medical_history' => 'إنزلاق غضروفي قطني خفيف حسب صورة رنين 2024. يتابع علاج طبيعي بشكل غير منتظم.',
            'allergies' => 'حساسية من الأسبرين',
            'current_medications' => 'باراسيتامول عند الحاجة فقط',
        ]);

        $this->medicalProfile($heba, [
            'height_cm' => 165,
            'weight_kg' => 60,
            'chronic_diseases' => 'فقر دم نقص الحديد (محسّن)',
            'medical_history' => 'سبق علاج فقر الدم عام 2023. تحاليل الهيموغلوبين الأخيرة ضمن الطبيعي.',
            'allergies' => 'لا توجد',
            'current_medications' => 'لا أدوية مزمنة حالياً',
        ]);

        $this->physicianProfile($ahmad, $admin, [
            'specialty' => 'أمراض القلب',
            'certificate' => 'بكالوريوس طب — الجامعة الإسلامية بغزة، اختصاص أمراض القلب — وزارة الصحة، ترخيص مزاولة سارٍ حتى 2027',
            'status' => PhysicianProfile::STATUS_APPROVED,
            'certName' => 'ترخيص-قلب-أحمد-العكلوك.pdf',
        ]);

        $this->physicianProfile($sara, $admin, [
            'specialty' => 'طب الأطفال',
            'certificate' => 'بكالوريوس طب — جامعة الأزهر بغزة، اختصاص أطفال — مستشفى النصر، عضوية نقابة الأطباء',
            'status' => PhysicianProfile::STATUS_APPROVED,
            'certName' => 'شهادة-أطفال-سارة-أبو-شمالة.pdf',
        ]);

        $this->physicianProfile($mohammad, $admin, [
            'specialty' => 'طب الأسرة',
            'certificate' => 'طب عام وطب أسرة — وزارة الصحة، خبرة عيادات مجتمعية في شمال غزة',
            'status' => PhysicianProfile::STATUS_APPROVED,
            'certName' => 'ترخيص-أسرة-محمد-البرغوثي.pdf',
        ]);

        $this->physicianProfile($lina, $admin, [
            'specialty' => 'الأمراض الجلدية',
            'certificate' => 'شهادة اختصاص جلدية — قيد مراجعة الإدارة. مرفقات الشهادة بانتظار التوثيق.',
            'status' => PhysicianProfile::STATUS_PENDING,
            'certName' => 'شهادة-جلدية-لينا-قيد-المراجعة.pdf',
        ]);

        // ——— استشارات واضحة (طابور + مباشرة + مكتملة) ———

        $queueA = $this->consultation([
            'patient' => $ali,
            'physician' => null,
            'mode' => Consultation::MODE_QUEUE,
            'status' => 'pending',
            'daysAgo' => 1,
            'question' => "منذ ثلاثة أيام أعاني من ضيق تنفس ليلاً مع سعال جاف.\nأستخدم بخاخ الفنتولين لكن التحسن مؤقت.\nهل يلزم صورة صدر أم يكفي تعديل البخاخ؟",
            'response' => null,
            'attach' => ['قياس-الاكسجين.pdf'],
        ]);

        $queueB = $this->consultation([
            'patient' => $heba,
            'physician' => null,
            'mode' => Consultation::MODE_QUEUE,
            'status' => 'pending',
            'daysAgo' => 0,
            'question' => "ظهور طفح جلدي على الذراعين بعد تناول مضاد حيوي قبل يومين.\nالطرح أحمر مع حرقة خفيفة بدون حمى.\nهل أوقف الدواء فوراً؟",
            'response' => null,
            'attach' => [],
        ]);

        $queueC = $this->consultation([
            'patient' => $yousef,
            'physician' => null,
            'mode' => Consultation::MODE_QUEUE,
            'status' => 'pending',
            'daysAgo' => 2,
            'question' => "ألم أسفل الظهر يزيد عند الجلوس الطويل، ويمتد أحياناً للساق اليسرى.\nما تمارين البيت الآمنة قبل تحديد موعد علاج طبيعي؟",
            'response' => null,
            'attach' => ['وصف-الالم.pdf'],
        ]);

        $directPending = $this->consultation([
            'patient' => $maryam,
            'physician' => $ahmad,
            'mode' => Consultation::MODE_DIRECT,
            'status' => 'pending',
            'daysAgo' => 1,
            'question' => "ضغط الدم صباحاً 148/92 مع صداع خفيف خلف الرأس.\nأخذ أملوديبين 5mg بانتظام.\nهل أرفع الجرعة أم أكرر القياس لأيام ثم نقرر؟",
            'response' => null,
            'attach' => ['قراءات-الضغط.pdf'],
        ]);

        $this->consultation([
            'patient' => $noor,
            'physician' => $sara,
            'mode' => Consultation::MODE_DIRECT,
            'status' => 'pending',
            'daysAgo' => 0,
            'question' => "ابنتي عمرها 3 سنوات، حرارة 38.5 منذ أمس مع احتقان أنف ورفض للأكل.\nأعطيتها باراسيتامول شراب مرة واحدة.\nمتى أراجع الطوارئ؟",
            'response' => null,
            'attach' => [],
        ]);

        $completedA = $this->consultation([
            'patient' => $maryam,
            'physician' => $ahmad,
            'mode' => Consultation::MODE_DIRECT,
            'status' => 'completed',
            'daysAgo' => 8,
            'hoursToReply' => 18,
            'question' => "نبض سريع أحياناً عند صعود الدرج، مع تعب عام.\nسكري مضبوط تقريباً (آخر تحليل سكر تراكمي 7.1).\nهل تحتاج تخطيط قلب أم متابعة سكر أولاً؟",
            'response' => "التوصية:\n1) قيسي النبض في الراحة والجهد وسجّليه ليومين.\n2) راجعي العيادة لإجراء تخطيط قلب ECG وفحص أملاح.\n3) استمري على الأدوية الحالية دون تعديل حتى ظهور النتائج.\n4) إذا ظهر ألم صدر أو ضيق تنفس شديد اذهبي للطوارئ فوراً.",
            'attach' => ['تحليل-سكر-تراكمي.pdf'],
        ]);

        $completedB = $this->consultation([
            'patient' => $ali,
            'physician' => $mohammad,
            'mode' => Consultation::MODE_QUEUE,
            'status' => 'completed',
            'daysAgo' => 12,
            'hoursToReply' => 30,
            'question' => "سعال ليلي منذ أسبوع مع صفير خفيف في الصدر.\nلا حمى حالياً. هل أبدأ كورس بخاخ ستيرويد؟",
            'response' => "ابدأ بخاخ الفنتولين عند الحاجة كل 4–6 ساعات عند الضيق.\nإذا استمر الصفير أكثر من 3 أيام أو ظهرت حمى/بلغم أصفر، راجع عيادة الصدر.\nتجنّب الغبار والمثيرات المنزلية، ولا تبدأ ستيرويد دون تقييم حضوري.",
            'attach' => [],
        ]);

        $completedC = $this->consultation([
            'patient' => $yousef,
            'physician' => $mohammad,
            'mode' => Consultation::MODE_DIRECT,
            'status' => 'completed',
            'daysAgo' => 5,
            'hoursToReply' => 10,
            'question' => "وزن 95 كغ وطول 180. أحس بتعب بعد الأكل الدسم.\nأريد خطة غذائية بسيطة تناسب الواقع في غزة.",
            'response' => "خطة مبدئية لأسبوعين:\n- قلّل المشروبات المحلّاة والخبز الأبيض.\n- زد الخضار والبقول وأكلات المنزل البسيطة.\n- امشِ 20–30 دقيقة يومياً إن أمكن.\n- أعد قياس الوزن بعد أسبوعين وأخبرنا بالنتيجة.",
            'attach' => [],
        ]);

        $completedD = $this->consultation([
            'patient' => $heba,
            'physician' => $sara,
            'mode' => Consultation::MODE_QUEUE,
            'status' => 'completed',
            'daysAgo' => 15,
            'hoursToReply' => 24,
            'question' => "دوخة خفيفة عند الوقوف السريع، وتحاليل قديمة أظهرت هيموغلوبين 10.8.\nهل أحتاج إعادة تحليل دم الآن؟",
            'response' => "نعم يُفضَّل إعادة صورة دم كاملة ومخزون الحديد.\nاشربي سوائل كافية وتجنّبي النهوض المفاجئ.\nإذا تكررت الإغماءات أو الشحوب الشديد راجعي المركز الصحي.",
            'attach' => ['تحليل-دم-قديم.pdf'],
        ]);

        // ردود متابعة على استشارة مكتملة
        ConsultationMessage::create([
            'consultation_id' => $completedA->id,
            'sender_id' => $maryam->id,
            'sender_role' => 'patient',
            'body' => 'شكراً دكتور. سجلت النبض صباحاً 88 وفي المشي 110. سأحجز تخطيط القلب إن شاء الله.',
        ]);

        ConsultationMessage::create([
            'consultation_id' => $completedA->id,
            'sender_id' => $ahmad->id,
            'sender_role' => 'physician',
            'body' => 'ممتاز. أرسلي نتيجة التخطيط هنا بعد إجرائه لنراجعها معاً.',
        ]);

        // استشارة مباشرة قيد الانتظار عند طبيب (مأخوذة من الطابور)
        $this->consultation([
            'patient' => $noor,
            'physician' => $sara,
            'mode' => Consultation::MODE_QUEUE,
            'status' => 'pending',
            'daysAgo' => 3,
            'question' => "طفلي عمره سنة ونصف يرفض الحليب الصناعي ويقبل شوربة الخضار فقط.\nالوزن يزداد ببطء. هل هذا طبيعي؟",
            'response' => null,
            'attach' => [],
        ]);

        unset($queueA, $queueB, $queueC, $directPending, $completedB, $completedC, $completedD, $physicians, $patients);
    }

    private function user(string $name, string $email, string $role): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'role' => $role,
                'email_verified_at' => now(),
                'is_disabled' => false,
            ]
        );
    }

    /** @param array<string, mixed> $data */
    private function medicalProfile(User $patient, array $data): void
    {
        MedicalProfile::updateOrCreate(
            ['user_id' => $patient->id],
            $data
        );
    }

    /** @param array{specialty:string,certificate:string,status:string,certName:string} $data */
    private function physicianProfile(User $doc, User $admin, array $data): void
    {
        $approved = $data['status'] === PhysicianProfile::STATUS_APPROVED;

        $profile = PhysicianProfile::updateOrCreate(
            ['user_id' => $doc->id],
            [
                'specialty' => $data['specialty'],
                'certificate' => $data['certificate'],
                'verification_status' => $data['status'],
                'verified_at' => $approved ? now()->subDays(20) : null,
                'verified_by' => $approved ? $admin->id : null,
                'rejection_reason' => null,
            ]
        );

        $file = $this->storeSeedFile(
            owner: $doc,
            relativeDir: 'seed/certificates/'.$doc->id,
            originalName: $data['certName'],
            kind: 'pdf',
            title: 'Certificate'
        );

        $profile->certificate_file_ids = [$file->id];
        $profile->certificate_file_id = $file->id;
        $profile->save();
    }

    /**
     * @param array{
     *   patient:User,
     *   physician:?User,
     *   mode:string,
     *   status:string,
     *   daysAgo:int,
     *   hoursToReply?:int,
     *   question:string,
     *   response:?string,
     *   attach:list<string>
     * } $data
     */
    private function consultation(array $data): Consultation
    {
        $submittedAt = now()->subDays($data['daysAgo'])->subHours(random_int(1, 8));
        $hasResponse = filled($data['response']);

        $consultation = Consultation::create([
            'patient_id' => $data['patient']->id,
            'physician_id' => $data['physician']?->id,
            'assignment_mode' => $data['mode'],
            'question_text' => $data['question'],
            'status' => $data['status'],
            'submitted_at' => $submittedAt,
            'physician_response' => $data['response'],
            'responded_at' => $hasResponse
                ? $submittedAt->copy()->addHours($data['hoursToReply'] ?? 12)
                : null,
        ]);

        foreach ($data['attach'] as $name) {
            $this->storeSeedFile(
                owner: $data['patient'],
                relativeDir: 'seed/consultations/'.$consultation->id,
                originalName: $name,
                kind: 'pdf',
                title: pathinfo($name, PATHINFO_FILENAME),
                consultationId: $consultation->id,
            );
        }

        return $consultation;
    }

    private function storeSeedFile(
        User $owner,
        string $relativeDir,
        string $originalName,
        string $kind,
        string $title,
        ?int $consultationId = null,
    ): MedicalFile {
        $safeName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME), '-') ?: 'file';
        $ext = pathinfo($originalName, PATHINFO_EXTENSION) ?: ($kind === 'pdf' ? 'pdf' : 'png');
        $path = $relativeDir.'/'.Str::uuid().'-'.$safeName.'.'.$ext;

        $bytes = $kind === 'pdf' ? $this->fakePdfBytes($title) : $this->fakePngBytes();
        Storage::disk('local')->put($path, $bytes);

        return MedicalFile::create([
            'owner_user_id' => $owner->id,
            'uploaded_by_user_id' => $owner->id,
            'consultation_id' => $consultationId,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $originalName,
            'mime_type' => $kind === 'pdf' ? 'application/pdf' : 'image/png',
            'size_bytes' => strlen($bytes),
            'file_kind' => $kind,
        ]);
    }

    private function fakePngBytes(): string
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMB/6X9qZ0AAAAASUVORK5CYII=',
            true
        ) ?: '';
    }

    private function fakePdfBytes(string $title): string
    {
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
