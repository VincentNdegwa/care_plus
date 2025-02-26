<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
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

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }
    public function patient()
    {
        return $this->hasOne(Patient::class, 'user_id');
    }

    public function doctor()
    {
        return $this->hasOne(Doctor::class, 'user_id');
    }

    public function userRole()
    {
        switch ($this->role) {
            case 'Patient':
                return $this->patient()->first();
            case 'Doctor':
                return $this->doctor()->first();
            case 'Caregiver':
                return $this->caregiver()->first();
            default:
                return $this->patient()->first();
        }
    }

    public function caregiver()
    {
        return $this->hasOne(Caregiver::class, 'user_id');
    }

    public function professionalProfile()
    {
        if ($this->role === "Doctor") {
            return $this->doctor();
        } elseif ($this->role === "Caregiver") {
            return $this->caregiver();
        }
        return null;
    }
    public function getAvatar()
    {
        $profile = $this->profile()->first();
        return $profile ? $profile->avatar : null;
    }

    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }

    public function getTimezone(){
        return $this->settings()->first()->settings["user_management"]["timezone"] ?? "Africa/Nairobi";
    }

    public function sendSms($message){
        $settings = $this->settings()->first();
        if ($settings && $settings->settings["user_management"]["notification_preferences"]["sms"] == true) {
            //dispatch a queue to send sms
        }
    }

    public function sendEmail(){
        $settings = $this->settings()->first();
        if ($settings && $settings->settings["user_management"]["notification_preferences"]["email"] == true) {
            //dispatch a queue to send email
        }
    }
}
