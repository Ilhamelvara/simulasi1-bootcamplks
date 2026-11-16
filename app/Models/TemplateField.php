<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateField extends Model
{
    use HasFactory;

    protected $table = 'template_fields';

    protected $fillable = [
        'template_id',
        'name',
        'slug',
        'type',
    ];

    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id');
    }

    public function fieldValues()
    {
        return $this->hasMany(SectionFieldValue::class, 'template_field_id');
    }
}

