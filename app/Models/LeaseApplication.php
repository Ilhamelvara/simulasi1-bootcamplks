<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaseApplication extends Model
{
    use HasFactory;

    protected $table = 'lease_applications';

    protected $fillable = [
        'developer_id',
        'cluster_id',
        'months',
        'monthly_nominal',
        'status',
        'notes',
    ];

    protected $casts = [
        'months'          => 'integer',
        'monthly_nominal' => 'float',
    ];

    public function developer()
    {
        return $this->belongsTo(Developer::class, 'developer_id');
    }

    public function cluster()
    {
        return $this->belongsTo(ServerCluster::class, 'cluster_id');
    }
}

