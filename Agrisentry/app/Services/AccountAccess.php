<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class AccountAccess
{
    public const FEATURES = ['dashboard', 'goats', 'health-logs', 'alerts', 'medical-records', 'collars', 'reports', 'gemini-advice'];

    public static function defaults(string $role): array
    {
        $permissions = [];
        foreach (self::FEATURES as $feature) {
            $permissions[$feature.'.read'] = true;
            $permissions[$feature.'.write'] = $role === 'Staff' || in_array($feature, ['medical-records', 'gemini-advice']);
        }
        return $permissions;
    }

    public static function permissions(string $role): array
    {
        $stored = DB::table('role_permissions')->where('role', $role)->value('permissions');
        return $stored ? array_merge(self::defaults($role), json_decode($stored, true)) : self::defaults($role);
    }

    public static function record(User $user, string $action, $request): void
    {
        DB::table('activity_logs')->insert(['user_id' => $user->id, 'username' => $user->username,
            'action' => $action, 'path' => $request->path(), 'ip_address' => $request->ip(), 'created_at' => now()]);
    }
}
