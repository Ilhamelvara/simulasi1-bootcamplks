<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Validation extends Model
{
    use HasFactory;

    protected $table = 'validations';

    protected $fillable = [
        'developer_id',
        'auditor_id',
        'github_url',
        'tech_stack',
        'monthly_income',
        'status',
        'auditor_notes',
    ];

    protected $casts = [
        'monthly_income' => 'float',
    ];

    public function developer()
    {
        return $this->belongsTo(Developer::class, 'developer_id');
    }

    public function auditor()
    {
        return $this->belongsTo(Auditor::class, 'auditor_id');
    }
}
