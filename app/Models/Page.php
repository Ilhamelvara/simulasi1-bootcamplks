<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $table = 'pages';

    protected $fillable = [
        'developer_id',
        'title',
        'slug',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function developer()
    {
        return $this->belongsTo(Developer::class, 'developer_id');
    }

    public function sections()
    {
        return $this->hasMany(PageSection::class, 'page_id')->orderBy('position', 'asc');
    }
}
