<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Developer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'developers';

    protected $fillable = [
        'full_name',
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
        return 'developer';
    }

    public function validations()
    {
        return $this->hasMany(Validation::class, 'developer_id');
    }

    public function latestValidation()
    {
        return $this->hasOne(Validation::class, 'developer_id')->latestOfMany();
    }

    public function pages()
    {
        return $this->hasMany(Page::class, 'developer_id');
    }

    public function leaseApplications()
    {
        return $this->hasMany(LeaseApplication::class, 'developer_id');
    }
}
