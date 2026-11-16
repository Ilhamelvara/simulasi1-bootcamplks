<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Auditor extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'auditors';

    protected $fillable = [
        'username',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'role',
    ];

    public function getRoleAttribute(): string
    {
        return 'auditor';
    }

    public function validations()
    {
        return $this->hasMany(Validation::class, 'auditor_id');
    }
}
