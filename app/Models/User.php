<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_PATIENT = 'patient';
    public const ROLE_PHYSICIAN = 'physician';
    public const ROLE_ADMIN = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function medicalProfile(): HasOne
    {
        return $this->hasOne(MedicalProfile::class);
    }

    public function physicianProfile(): HasOne
    {
        return $this->hasOne(PhysicianProfile::class);
    }

    public function consultationsAsPatient(): HasMany
    {
        return $this->hasMany(Consultation::class, 'patient_id');
    }

    public function consultationsAsPhysician(): HasMany
    {
        return $this->hasMany(Consultation::class, 'physician_id');
    }


    public function ownedMedicalFiles(): HasMany
    {
        return $this->hasMany(MedicalFile::class, 'owner_user_id');
    }

    public function uploadedMedicalFiles(): HasMany
    {
        return $this->hasMany(MedicalFile::class, 'uploaded_by_user_id');
    }

    // caregiver feature removed
}
