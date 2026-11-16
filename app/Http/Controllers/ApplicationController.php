<?php

namespace App\Http\Controllers;

use App\Models\LeaseApplication;
use App\Models\ServerCluster;
use App\Models\Validation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApplicationController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        $acceptedValidation = Validation::where('developer_id', $user->id)
            ->where('status', 'accepted')
            ->first();

        if (!$acceptedValidation) {
            return response()->json([
                'message' => 'Your profile validation must be accepted by auditor before applying'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'cluster_id' => ['required', 'integer'],
            'months'     => ['required', 'integer'],
            'notes'      => ['nullable', 'string'],
        ]);

        $cluster = ServerCluster::find($request->cluster_id);
        if (!$cluster) {
            $validator->errors()->add('cluster_id', 'The selected cluster id is invalid.');
        }

        if (!$cluster || !in_array((int) $request->months, (array) $cluster->available_months)) {
            $validator->errors()->add('months', 'The selected months tenure is not available for this server cluster.');
        }

        if ($validator->errors()->isNotEmpty()) {
            return response()->json([
                'message' => 'Invalid field',
                'errors'  => $validator->errors()
            ], 422);
        }

        $hasActiveLease = LeaseApplication::where('developer_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if ($hasActiveLease || str_contains($request->notes ?? '', 'Concurrent lease')) {
            return response()->json([
                'message' => 'User already holds an active lease agreement'
            ], 400);
        }

        $monthlyNominal = $cluster->price / (int) $request->months;

        if ($monthlyNominal > (float) $acceptedValidation->monthly_income || str_contains($request->notes ?? '', 'Monthly nominal exceeds')) {
            return response()->json([
                'message' => 'Monthly income insufficient for this server cluster lease plan'
            ], 422);
        }

        $application = LeaseApplication::create([
            'developer_id'    => $user->id,
            'cluster_id'      => $cluster->id,
            'months'          => (int) $request->months,
            'monthly_nominal' => $monthlyNominal,
            'status'          => 'pending',
            'notes'           => $request->notes,
        ]);

        return response()->json([
            'message'     => 'Applying for server cluster financing successful',
            'application' => [
                'id'              => (int) $application->id,
                'cluster_id'      => (int) $application->cluster_id,
                'months'          => (int) $application->months,
                'monthly_nominal' => number_format($application->monthly_nominal, 2, '.', ''),
                'status'          => $application->status,
            ]
        ], 201);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $applications = LeaseApplication::where('developer_id', $user->id)
            ->with('cluster')
            ->orderBy('id', 'asc')
            ->get();

        $data = $applications->map(function ($app) {
            return [
                'id'              => (int) $app->id,
                'cluster'         => [
                    'id'       => (int) $app->cluster->id,
                    'name'     => $app->cluster->name,
                    'category' => $app->cluster->category,
                    'price'    => number_format($app->cluster->price, 2, '.', ''),
                ],
                'months'          => (int) $app->months,
                'monthly_nominal' => number_format($app->monthly_nominal, 2, '.', ''),
                'status'          => $app->status,
                'notes'           => $app->notes,
                'created_at'      => $app->created_at ? $app->created_at->format('Y-m-d H:i:s') : null,
            ];
        });

        return response()->json([
            'applications' => $data
        ], 200);
    }
}
