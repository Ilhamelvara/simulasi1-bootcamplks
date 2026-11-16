<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServerCluster extends Model
{
    use HasFactory;

    protected $table = 'server_clusters';

    protected $fillable = [
        'name',
        'category',
        'price',
        'stock',
        'available_months',
    ];

    protected $casts = [
        'available_months' => 'array',
        'price'            => 'float',
        'stock'            => 'integer',
    ];

    public function applications()
    {
        return $this->hasMany(LeaseApplication::class, 'cluster_id');
    }
}
