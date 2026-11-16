<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    protected $table = 'templates';

    protected $fillable = [
        'name',
        'slug',
    ];

    public function fields()
    {
        return $this->hasMany(TemplateField::class, 'template_id');
    }

    public function sections()
    {
        return $this->hasMany(PageSection::class, 'template_id');
    }
}
