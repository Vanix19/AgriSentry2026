<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirebaseTelemetry;
use Illuminate\Http\Request;

class FirebaseSessionController extends Controller
{
    public function __invoke(Request $request, FirebaseTelemetry $firebase)
    {
        if (!$firebase->enabled()) return response()->json(['enabled' => false]);
        try {
            return response()->json($firebase->session($request->user()))->header('Cache-Control', 'no-store');
        } catch (\Throwable $error) {
            \App\Support\DeploymentErrors::log('Firebase session unavailable.', $error);
            return response()->json(['message' => 'Live sensor feed is unavailable. Standard updates remain available.'], 503);
        }
    }
}
