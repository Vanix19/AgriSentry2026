<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaretakerPhoneNumber;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        return response()->json([
            'users' => User::with('phoneNumbers')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'username' => 'required|string|max:100|unique:users,username',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(['Admin', 'Staff', 'Caretaker'])],
            'phone_numbers' => 'nullable|array',
            'phone_numbers.*.phone_number' => 'required_with:phone_numbers|string|max:20|distinct',
            'phone_numbers.*.label' => 'nullable|string|max:100',
        ]);

        if ($request->user()->role === 'Staff' && $validated['role'] !== 'Caretaker') {
            abort(403, 'Staff accounts may only add Caretaker accounts.');
        }

        if ($validated['role'] === 'Caretaker' && empty($validated['phone_numbers'])) {
            throw ValidationException::withMessages([
                'phone_numbers' => 'At least one phone number is required for Caretaker accounts (used for SMS alerts).',
            ]);
        }

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        foreach ($validated['phone_numbers'] ?? [] as $index => $phone) {
            $user->phoneNumbers()->create([
                'phone_number' => $phone['phone_number'],
                'label' => $phone['label'] ?? null,
                'is_primary' => $index === 0,
            ]);
        }

        return response()->json([
            'message' => 'User added successfully.',
            'user' => $user->load('phoneNumbers'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,'.$id,
            'username' => 'sometimes|string|max:100|unique:users,username,'.$id,
            'password' => 'nullable|string|min:8',
            'role' => ['sometimes', Rule::in(['Admin', 'Staff', 'Caretaker'])],
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if (isset($validated['password'])) $user->password_change_required = true;
        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $user->load('phoneNumbers'),
        ]);
    }

    public function destroy($id)
    {
        User::findOrFail($id)->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }

    public function addPhoneNumber(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'phone_number' => 'required|string|max:20',
            'label' => 'nullable|string|max:100',
        ]);

        $phone = $user->phoneNumbers()->firstOrCreate(
            ['phone_number' => trim($validated['phone_number'])],
            ['label' => $validated['label'] ?? null, 'is_primary' => !$user->phoneNumbers()->exists()],
        );

        return response()->json([
            'message' => $phone->wasRecentlyCreated ? 'Phone number added successfully.' : 'Phone number is already registered.',
            'phone_number' => $phone,
        ], $phone->wasRecentlyCreated ? 201 : 200);
    }

    public function destroyPhoneNumber($id)
    {
        CaretakerPhoneNumber::findOrFail($id)->delete();

        return response()->json(['message' => 'Phone number removed successfully.']);
    }
}
