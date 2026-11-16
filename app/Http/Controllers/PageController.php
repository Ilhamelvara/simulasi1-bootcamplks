<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\PageSection;
use App\Models\SectionFieldValue;
use App\Models\Validation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PageController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string'],
            'slug'  => ['required', 'string', 'unique:pages,slug'],
        ]);

        $page = Page::create([
            'developer_id' => $request->user()->id,
            'title'        => $validated['title'],
            'slug'         => $validated['slug'],
            'is_published' => false,
        ]);

        return response()->json([
            'message' => 'Page created successfully',
            'page'    => [
                'id'           => $page->id,
                'title'        => $page->title,
                'slug'         => $page->slug,
                'is_published' => (bool) $page->is_published,
            ],
        ], 201);
    }

    public function appendSection(Request $request, $slug)
    {
        $page = Page::where('slug', $slug)
            ->where('developer_id', $request->user()->id)
            ->first();

        if (!$page) {
            return response()->json([
                'message' => 'Page not found'
            ], 404);
        }

        $validated = $request->validate([
            'template_id' => ['required', 'integer', 'exists:templates,id'],
            'position'    => ['required', 'integer', 'min:1'],
        ]);

        $targetPosition = (int) $validated['position'];

        $section = DB::transaction(function () use ($page, $validated, $targetPosition) {
            $isOccupied = PageSection::where('page_id', $page->id)
                ->where('position', $targetPosition)
                ->exists();

            if ($isOccupied) {
                PageSection::where('page_id', $page->id)
                    ->where('position', '>=', $targetPosition)
                    ->orderBy('position', 'desc')
                    ->increment('position');
            }

            return PageSection::create([
                'page_id'     => $page->id,
                'template_id' => $validated['template_id'],
                'position'    => $targetPosition,
            ]);
        });

        return response()->json([
            'message' => 'Section appended successfully',
            'section' => [
                'id'          => $section->id,
                'template_id' => (int) $section->template_id,
                'position'    => (int) $section->position,
            ],
        ], 201);
    }

    public function updateFields(Request $request, $slug, $section_id)
    {
        $page = Page::where('slug', $slug)
            ->where('developer_id', $request->user()->id)
            ->first();

        if (!$page) {
            return response()->json([
                'message' => 'Page not found'
            ], 404);
        }

        $section = PageSection::where('page_id', $page->id)
            ->where('id', $section_id)
            ->first();

        if (!$section) {
            return response()->json([
                'message' => 'Section not found'
            ], 404);
        }

        $validated = $request->validate([
            'fields'                       => ['required', 'array'],
            'fields.*.template_field_id'   => ['required', 'integer'],
            'fields.*.value'               => ['required', 'string'],
        ]);

        DB::transaction(function () use ($section, $validated) {
            foreach ($validated['fields'] as $fieldData) {
                SectionFieldValue::updateOrCreate([
                    'page_section_id'   => $section->id,
                    'template_field_id' => $fieldData['template_field_id'],
                ], [
                    'value'             => $fieldData['value'],
                ]);
            }
        });

        return response()->json([
            'message' => 'Fields persisted successfully'
        ], 200);
    }

    public function publish(Request $request, $slug)
    {
        $page = Page::where('slug', $slug)
            ->where('developer_id', $request->user()->id)
            ->first();

        if (!$page) {
            return response()->json([
                'message' => 'Page not found'
            ], 404);
        }

        $isAccepted = Validation::where('developer_id', $request->user()->id)
            ->where('status', 'accepted')
            ->exists();

        if (!$isAccepted) {
            return response()->json([
                'message' => 'Developer profile validation is not accepted yet. Cannot publish page.'
            ], 403);
        }

        $page->update([
            'is_published' => true,
        ]);

        return response()->json([
            'message' => 'Page published successfully'
        ], 200);
    }

    public function discovery($slug)
    {
        $page = Page::where('slug', $slug)
            ->where('is_published', true)
            ->with(['developer.validations', 'sections.fieldValues'])
            ->first();

        if (!$page) {
            return response()->json([
                'message' => 'Page not found or not published'
            ], 404);
        }

        $developer = $page->developer;
        $acceptedValidation = $developer ? $developer->validations->where('status', 'accepted')->first() : null;
        $githubUrl = $acceptedValidation ? $acceptedValidation->github_url : null;

        $sections = $page->sections->map(function ($sec) {
            return [
                'id'          => $sec->id,
                'position'    => (int) $sec->position,
                'template_id' => (int) $sec->template_id,
                'fields'      => $sec->fieldValues->map(function ($fv) {
                    return [
                        'template_field_id' => (int) $fv->template_field_id,
                        'value'             => $fv->value,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'page' => [
                'title'     => $page->title,
                'slug'      => $page->slug,
                'developer' => [
                    'full_name'  => $developer ? $developer->full_name : '',
                    'github_url' => $githubUrl,
                ],
                'sections'  => $sections,
            ]
        ], 200);
    }
}

