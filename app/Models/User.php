<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_enrolled',
        'enrollment_id',
        'fcm_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_enrolled' => 'boolean',
        ];
    }

    public function enrollmentRequests()
    {
        return $this->hasMany(EnrollmentRequest::class);
    }

    public function testAttempts()
    {
        return $this->hasMany(TestAttempt::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function entitlements()
    {
        return $this->hasMany(Entitlement::class);
    }

    public function activeEntitlements()
    {
        return $this->entitlements()->whereNull('revoked_at');
    }

    public function tutorProfile()
    {
        return $this->hasOne(Tutor::class);
    }

    public function sessionsAsStudent()
    {
        return $this->hasMany(Session::class, 'student_id');
    }

    public function batchBookings()
    {
        return $this->hasMany(BatchBooking::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function claimedRoster()
    {
        return $this->hasOne(InstituteRoster::class, 'claimed_by');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Filament admin panel access: admins only (institute staff).
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin();
    }

    public function isInstructor(): bool
    {
        return $this->role === 'instructor';
    }

    /**
     * Does the user currently have access to this purchasable?
     * Access checks always go through entitlements, never purchases.
     */
    public function canAccess(string $type, int $id): bool
    {
        return $this->activeEntitlements()
            ->where('entitled_type', $type)
            ->where('entitled_id', $id)
            ->exists();
    }

    /**
     * Free tests remaining for a module: 2 for guests, 5 for enrolled.
     * Only 'submitted' attempts that consumed quota count — free tests
     * (is_free) and entitled (purchased/granted) attempts don't.
     */
    public function freeTestsRemaining(Module $module): int
    {
        $allowance = $this->is_enrolled ? 5 : 2;

        $used = $this->testAttempts()
            ->where('status', 'submitted')
            ->where('used_free_quota', true)
            ->whereHas('test', fn ($q) => $q->where('module_id', $module->id))
            ->count();

        return max(0, $allowance - $used);
    }
}
