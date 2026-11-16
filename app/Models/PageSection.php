<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageSection extends Model
{
    use HasFactory;

    protected $table = 'page_sections';

    protected $fillable = [
        'page_id',
        'template_id',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
        'template_id' => 'integer',
    ];

    public function page()
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id');
    }

    public function fieldValues()
    {
        return $this->hasMany(SectionFieldValue::class, 'page_section_id');
    }
}
