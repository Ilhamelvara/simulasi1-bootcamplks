<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SectionFieldValue extends Model
{
    use HasFactory;

    protected $table = 'section_field_values';

    protected $fillable = [
        'page_section_id',
        'template_field_id',
        'value',
    ];

    protected $casts = [
        'template_field_id' => 'integer',
        'page_section_id' => 'integer',
    ];

    public function section()
    {
        return $this->belongsTo(PageSection::class, 'page_section_id');
    }

    public function templateField()
    {
        return $this->belongsTo(TemplateField::class, 'template_field_id');
    }
}

