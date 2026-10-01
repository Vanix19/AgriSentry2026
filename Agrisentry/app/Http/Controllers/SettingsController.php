<?php

namespace App\Http\Controllers;

use App\Services\AccountAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load('phoneNumbers');
        return $request->expectsJson() ? response()->json(['user' => $user]) : view('settings', ['user' => $user]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_.-]+$/', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone_number' => ['nullable', 'string', 'regex:/^\+?[0-9]{10,15}$/'],
            'current_password' => 'required|string',
        ]);
        if (!Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Current password is incorrect.']);
        }
        DB::transaction(function () use ($user, $data, $request) {
            if ($user->email !== $data['email']) $user->email_verified_at = null;
            $user->fill(collect($data)->only(['name', 'username', 'email'])->all())->save();
            if (!empty($data['phone_number'])) {
                $user->phoneNumbers()->update(['is_primary' => false]);
                $user->phoneNumbers()->updateOrCreate(['phone_number' => $data['phone_number']], ['is_primary' => true]);
            }
            DB::table('password_otps')->where('user_id', $user->id)->delete();
            AccountAccess::record($user, 'Profile updated', $request);
        });
        return $request->expectsJson() ? response()->json(['message' => 'Profile updated.', 'user' => $user->fresh()->load('phoneNumbers')])
            : back()->with('status', 'Your profile has been updated.');
    }
}
