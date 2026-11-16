<?php

namespace App\Http\Controllers;

use App\Models\ServerCluster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServerClusterController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'category'  => ['nullable', 'string', 'in:bare_metal,gpu_cluster'],
            'max_price' => ['nullable', 'numeric'],
            'sort_by'   => ['nullable', 'string', 'in:price,name'],
            'order'     => ['nullable', 'string', 'in:asc,desc'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid query parameters'
            ], 422);
        }

        $query = ServerCluster::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        $sortBy = $request->query('sort_by', 'price');
        $order  = $request->query('order', 'asc');

        $clusters = $query->orderBy($sortBy, $order)->get();

        $data = $clusters->map(function ($c) {
            return [
                'id'               => (int) $c->id,
                'name'             => $c->name,
                'category'         => $c->category,
                'price'            => number_format($c->price, 2, '.', ''),
                'available_months' => array_map('intval', (array) $c->available_months),
            ];
        });

        return response()->json([
            'clusters' => $data
        ], 200);
    }
}

