<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AccountAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AccessController extends Controller
{
    public function index() {
        return response()->json(['features' => AccountAccess::FEATURES, 'roles' => [
            'Staff' => AccountAccess::permissions('Staff'), 'Caretaker' => AccountAccess::permissions('Caretaker')]]);
    }
    public function update(Request $request) {
        $data = $request->validate(['role' => ['required', Rule::in(['Staff', 'Caretaker'])],
            'permissions' => 'required|array', 'permissions.*' => 'boolean']);
        $permissions = array_intersect_key($data['permissions'], AccountAccess::defaults($data['role']));
        DB::table('role_permissions')->updateOrInsert(['role' => $data['role']], ['permissions' => json_encode($permissions)]);
        return response()->json(['message' => 'Permissions saved.']);
    }
    public function logs(Request $request) {
        return response()->json(DB::table('activity_logs')->when($request->query('user_id'),
            fn ($query, $id) => $query->where('user_id', $id))->orderByDesc('id')->paginate(50));
    }
}
