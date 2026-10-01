<?php

namespace App\Http\Controllers;

use App\Models\Goat;

class GoatProfileController extends Controller
{
    public function show($id)
    {
        $user = auth()->user();
        $permissions = \App\Services\AccountAccess::permissions($user->role);
        abort_unless($user->isAdmin() || ($permissions['goats.read'] ?? false), 403);
        $goat = Goat::with([
            'medicalRecords' => fn ($q) => $q->latest(),
            'healthLogs' => fn ($q) => $q->latest(),
            'alerts' => fn ($q) => $q->latest(),
            'collar',
        ])->findOrFail($id);

        if (!$user->isAdmin()) {
            foreach (['healthLogs' => 'health-logs', 'medicalRecords' => 'medical-records', 'alerts' => 'alerts'] as $relation => $feature) {
                if (!$permissions[$feature.'.read']) $goat->setRelation($relation, collect());
            }
            if (!$permissions['collars.read']) $goat->setRelation('collar', null);
        }

        return view('goat-profile', [
            'goat' => $goat,
            'healthLogs' => $goat->healthLogs,
            'medicalRecords' => $goat->medicalRecords,
            'canManageGoat' => $user->isAdmin() || $permissions['goats.write'],
            'canEdit' => $user->isAdmin() || $permissions['medical-records.write'],
        ]);
    }
}
