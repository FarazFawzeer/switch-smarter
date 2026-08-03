<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'employee_id',
        'address',
        'telephone',
        'age',
        'gender',
        'dob',
        'email',
        'contact_no',
        'email_verified_at',
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'image_path',
        'active',
        'deleted',
        'type',
        'country_code',
        'enrolment_number',
        'supervisor_id',
        'engineer_id',
        'shift_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function engineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'engineer_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function supervisors(): HasMany
    {
        return $this->hasMany(User::class, 'engineer_id');
    }

    public function technicians(): HasMany
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    public function assignableTechnicians()
    {
        return match ($this->type) {
            'Supervisor' => $this->technicians(),
            'Engineer'   => User::where('type', 'Technician')
                ->whereIn('supervisor_id', $this->supervisors()->pluck('id')),
            default      => User::where('type', 'Technician'),
        };
    }

    public function routes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Route::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }
}