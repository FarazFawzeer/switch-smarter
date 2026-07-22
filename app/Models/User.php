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

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'telephone',
        'age',
        'gender',
        'dob',
        'email',
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
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The engineer this supervisor reports to.
     */
    public function engineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'engineer_id');
    }

    /**
     * The supervisor this technician reports to.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Supervisors reporting to this engineer.
     */
    public function supervisors(): HasMany
    {
        return $this->hasMany(User::class, 'engineer_id');
    }

    /**
     * Technicians reporting to this supervisor.
     */
    public function technicians(): HasMany
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }


    /**
     * All technicians this user can assign work to, based on their role.
     * - Supervisor: only their own technicians
     * - Engineer: all technicians under all their supervisors
     * - Manager/Admin: all technicians company-wide
     */
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
}
