<?php

namespace App\Models;

use App\AccountApprovalStatus;
use App\AccountStatus;
use App\Role;
use App\VerificationLevel;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'phone', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUlids, Notifiable;

    protected $keyType = 'string';

    public $incrementing = false;

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function travelerProfile(): HasOne
    {
        return $this->hasOne(TravelerProfile::class);
    }

    public function operatorProfile(): HasOne
    {
        return $this->hasOne(OperatorProfile::class);
    }

    public function vehicleOwnerProfile(): HasOne
    {
        return $this->hasOne(VehicleOwnerProfile::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class, 'operator_id');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'owner_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'traveler_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'author_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function conversationParticipants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function verificationRequests(): HasMany
    {
        return $this->hasMany(VerificationRequest::class);
    }

    public function adminLogs(): HasMany
    {
        return $this->hasMany(AdminLog::class, 'admin_id');
    }

    public function otpChallenges(): HasMany
    {
        return $this->hasMany(OtpChallenge::class);
    }

    public function referralsMade(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function referral(): HasOne
    {
        return $this->hasOne(Referral::class, 'referred_user_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'approval_reviewed_at' => 'datetime',
            'password' => 'hashed',
            'approval_status' => AccountApprovalStatus::class,
            'account_status' => AccountStatus::class,
            'role' => Role::class,
            'verification_level' => VerificationLevel::class,
        ];
    }
}
