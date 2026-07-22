<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    protected $fillable = ['route_no', 'description'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function supervisors()
    {
        return $this->users()->where('type', 'Supervisor')->get();
    }

    public function technicians()
    {
        return $this->users()->where('type', 'Technician')->get();
    }

    public function technician(): ?User
    {
        return $this->users()->where('type', 'Technician')->first();
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}