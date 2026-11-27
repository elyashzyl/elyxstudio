<?php

namespace App\Http\Controllers;

use App\Models\Quarter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuarterController extends Controller
{
    public function index(): Response
    {
        $quarters = Quarter::withCount([
            'teachingMaterials',
            'games',
            'quizzes',
            'seatworks',
            'practicals',
            'exams',
        ])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return Inertia::render('Teacher/Quarters/Index', [
            'quarters' => $quarters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:quarters,name'],
            'code' => ['nullable', 'string', 'max:20', 'unique:quarters,code'],
            'description' => ['nullable', 'string', 'max:500'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        Quarter::create([
            'name' => trim($data['name']),
            'code' => ! empty($data['code']) ? strtoupper(trim($data['code'])) : null,
            'description' => $data['description'] ?? null,
            'display_order' => $data['display_order'] ?? 1,
            'is_active' => $data['is_active'] ?? true,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ]);

        return redirect()->route('teacher.quarters.index')
            ->with('success', 'Quarter created successfully.');
    }

    public function update(Request $request, Quarter $quarter): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:quarters,name,' . $quarter->id],
            'code' => ['nullable', 'string', 'max:20', 'unique:quarters,code,' . $quarter->id],
            'description' => ['nullable', 'string', 'max:500'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $quarter->update([
            'name' => trim($data['name']),
            'code' => ! empty($data['code']) ? strtoupper(trim($data['code'])) : null,
            'description' => $data['description'] ?? null,
            'display_order' => $data['display_order'] ?? 1,
            'is_active' => $data['is_active'] ?? true,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ]);

        return redirect()->route('teacher.quarters.index')
            ->with('success', 'Quarter updated successfully.');
    }

    public function destroy(Quarter $quarter): RedirectResponse
    {
        $quarter->delete();

        return redirect()->route('teacher.quarters.index')
            ->with('success', 'Quarter deleted successfully.');
    }
}
