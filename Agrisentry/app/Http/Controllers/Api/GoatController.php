<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Goat;
use App\Services\HealthAlertEvaluator;
use Illuminate\Http\Request;

class GoatController extends Controller
{
    public function __construct(private readonly HealthAlertEvaluator $evaluator)
    {
    }

    public function index()
    {
        $goats = Goat::with(['medicalRecords', 'collar'])
            ->orderByRaw("CASE
                WHEN status = 'Urgent' THEN 1
                WHEN status IN ('Warning', 'Monitoring') THEN 2
                ELSE 3
            END")
            ->latest()
            ->get();

        return response()->json([
            'goats' => $goats
        ]);
    }

    public function store(Request $request)
    {
        $this->useEarTag($request);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:goats,code',
            'breed' => 'nullable|in:Boer,Anglo-Nubian,Saanen,Alpine,Toggenburg,Native,Crossbreed,Other',
            'age' => 'nullable|string|max:100',
            'sex' => 'nullable|string|max:100',
            'pregnancy_status' => 'sometimes|required|in:unknown,pregnant,not_pregnant',
            'weight' => 'nullable|string|max:100',
            'owner' => 'nullable|string|max:255',
            'ear_tag' => 'sometimes|required|string|max:100|regex:/^[A-Za-z0-9][A-Za-z0-9_-]*$/',
            'color' => 'nullable|string|max:100',
            'collar_id' => 'nullable|string|max:255',
            'temperature' => 'nullable|numeric',
            'movement' => 'nullable|string|max:255',
            'battery' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:100',
            'alert_reason' => 'nullable|string',
        ], ['code.regex' => 'Goat ID must use GT- followed by three digits, for example GT-014.']);

        $this->validatePregnancy($validated);

        if (!isset($validated['status'])) {
            $validated = array_merge($validated, HealthAlertEvaluator::resolveStatus($validated['temperature'] ?? null));
        }

        $goat = Goat::create($validated);

        if ($goat->temperature !== null) {
            $this->evaluator->evaluate($goat->fresh(), $goat->collar);
        }

        return response()->json([
            'message' => 'Goat added successfully.',
            'goat' => $goat
        ], 201);
    }

    public function show($id)
    {
        $goat = Goat::with(['medicalRecords', 'healthLogs', 'alerts', 'collar'])->findOrFail($id);

        return response()->json([
            'goat' => $goat
        ]);
    }

    public function update(Request $request, $id)
    {
        $goat = Goat::findOrFail($id);
        $this->useEarTag($request, $goat);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:100|unique:goats,code,' . $id,
            'breed' => 'nullable|in:Boer,Anglo-Nubian,Saanen,Alpine,Toggenburg,Native,Crossbreed,Other',
            'age' => 'nullable|string|max:100',
            'sex' => 'nullable|string|max:100',
            'pregnancy_status' => 'sometimes|required|in:unknown,pregnant,not_pregnant',
            'weight' => 'nullable|string|max:100',
            'owner' => 'nullable|string|max:255',
            'ear_tag' => 'sometimes|required|string|max:100|regex:/^[A-Za-z0-9][A-Za-z0-9_-]*$/',
            'color' => 'nullable|string|max:100',
            'collar_id' => 'nullable|string|max:255',
            'temperature' => 'nullable|numeric',
            'movement' => 'nullable|string|max:255',
            'battery' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:100',
            'alert_reason' => 'nullable|string',
        ], ['code.regex' => 'Goat ID must use GT- followed by three digits, for example GT-014.']);

        $this->validatePregnancy($validated, $goat);

        if (array_key_exists('temperature', $validated) && !isset($validated['status'])) {
            $validated = array_merge($validated, HealthAlertEvaluator::resolveStatus($validated['temperature']));
        }

        $goat->update($validated);

        if (array_key_exists('temperature', $validated) && $goat->temperature !== null) {
            $this->evaluator->evaluate($goat->fresh(), $goat->collar);
        }

        return response()->json([
            'message' => 'Goat updated successfully.',
            'goat' => $goat
        ]);
    }

    private function validatePregnancy(array $attributes, ?Goat $goat = null): void
    {
        $sex = strtolower(trim((string) ($attributes['sex'] ?? $goat?->sex)));
        $pregnancy = $attributes['pregnancy_status'] ?? $goat?->pregnancy_status ?? 'unknown';
        if ($sex === 'male' && $pregnancy === 'pregnant') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'pregnancy_status' => 'A male goat cannot be marked pregnant. Update the sex or pregnancy status.',
            ]);
        }
    }

    private function useEarTag(Request $request, ?Goat $goat = null): void
    {
        if (!$request->exists('ear_tag') && $request->exists('code')) {
            // Compatibility for older installed clients that still submit Goat ID.
            $request->validate(['code' => 'required|regex:/^GT-[0-9]{3}$/']);
            $request->merge(['ear_tag' => $request->input('code')]);
        }
        if (!$goat || $request->exists('ear_tag')) {
            $earTag = $request->input('ear_tag', '');
            $request->merge(['ear_tag' => is_string($earTag) ? trim($earTag) : $earTag]);
            $request->validate(['ear_tag' => ['required','string','max:100','regex:/^[A-Za-z0-9][A-Za-z0-9_-]*$/',
                \Illuminate\Validation\Rule::unique('goats', 'code')->ignore($goat?->id),
                \Illuminate\Validation\Rule::unique('goats', 'ear_tag')->ignore($goat?->id)]],
                ['ear_tag.unique' => 'This ear tag is already registered.', 'ear_tag.regex' => 'Use letters, numbers, hyphens, or underscores for the ear tag.']);
            $request->merge(['code' => $request->input('ear_tag')]);
        }
    }

    public function destroy($id)
    {
        $goat = Goat::findOrFail($id);
        $goat->delete();

        return response()->json([
            'message' => 'Goat deleted successfully.'
        ]);
    }
}
