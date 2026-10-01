<?php
namespace App\Http\Middleware;

use App\Services\AccountAccess;
use Closure;
use Illuminate\Http\Request;

class FeatureAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $feature = $request->segment(2);
        if ($feature === 'goats' && $request->segment(4) === 'medical-records') $feature = 'medical-records';
        if (!$user->isAdmin()) {
            abort_unless(in_array($user->role, ['Staff', 'Caretaker']), 403);
            if ($feature === 'firebase') {
                foreach (AccountAccess::FEATURES as $name) abort_unless(AccountAccess::permissions($user->role)[$name.'.read'], 403);
            }
            if (in_array($feature, ['users', 'phone-numbers', 'access-control', 'activity-logs'])) abort(403);
            if (in_array($feature, AccountAccess::FEATURES)) {
                $key = $feature.($request->isMethod('GET') ? '.read' : '.write');
                abort_unless(AccountAccess::permissions($user->role)[$key] ?? false, 403, 'Your account does not have access to this function.');
            }
        }
        $response = $next($request);
        if (!$user->isAdmin() && $response instanceof \Illuminate\Http\JsonResponse) {
            $permissions = AccountAccess::permissions($user->role);
            $filter = function ($data) use (&$filter, $permissions) {
                if (!is_array($data)) return $data;
                foreach (['latest_logs' => 'health-logs', 'health_logs' => 'health-logs', 'medical_records' => 'medical-records', 'alerts' => 'alerts', 'collar' => 'collars', 'goats' => 'goats'] as $key => $name) {
                    if (!($permissions[$name.'.read'] ?? false)) unset($data[$key]);
                }
                return array_map($filter, $data);
            };
            $response->setData($filter($response->getData(true)));
        }
        if (!$request->isMethod('GET') && $response->getStatusCode() < 400 && $feature !== 'firebase') {
            AccountAccess::record($user, $request->method().' '.$feature, $request);
        }
        return $response;
    }
}
