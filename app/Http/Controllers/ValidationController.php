<?php

namespace App\Http\Controllers;

use App\Models\Validation;
use Illuminate\Http\Request;

class ValidationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'github_url'     => ['required', 'url'],
            'tech_stack'     => ['required', 'string'],
            'monthly_income' => ['required', 'numeric'],
        ], [
            'monthly_income.numeric' => 'The monthly income field must be a valid number.',
        ]);

        $user = $request->user();

        $existing = Validation::where('developer_id', $user)
            ->whereIn('status', ['pending', 'accepted'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'A validation request has already been submitted and is either pending or already approved'
            ], 400);
        }

        Validation::create([
            'developer_id'   => $user,
            'github_url'     => $validated['github_url'],
            'tech_stack'     => $validated['tech_stack'],
            'monthly_income' => $validated['monthly_income'],
            'status'         => 'pending',
        ]);

        return response()->json([
            'message' => 'Request data validation sent successful'
        ], 200);
    }

    public function show(Request $request)
    {
        $user = $request->user();

        $validation = Validation::where('developer_id', $user->id)
            ->with('auditor')
            ->latest()
            ->first();

        if (!$validation) {
            return response()->json([
                'validation' => null
            ], 200);
        }

        return response()->json([
            'validation' => [
                'id'             => $validation->id,
                'status'         => $validation->status,
                'github_url'     => $validation->github_url,
                'tech_stack'     => $validation->tech_stack,
                'monthly_income' => (float) $validation->monthly_income,
                'auditor_notes'  => $validation->auditor_notes,
                'auditor'        => $validation->auditor ? [
                    'id'       => $validation->auditor->id,
                    'username' => $validation->auditor->username,
                ] : null,
            ]
        ], 200);
    }

    public function review(Request $request, $id)
    {
        $validated = $request->validate([
            'status'        => ['required', 'in:accepted,declined'],
            'auditor_notes' => ['nullable', 'string'],
        ]);

        $validation = Validation::find($id);

        if (!$validation) {
            return response()->json([
                'message' => 'Validation not found'
            ], 404);
        }

        $validation->update([
            'status'        => $validated['status'],
            'auditor_notes' => $validated['auditor_notes'] ?? null,
            'auditor_id'    => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Validation status updated successfully'
        ], 200);
    }
}
