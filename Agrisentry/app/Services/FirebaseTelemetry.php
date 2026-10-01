<?php

namespace App\Services;

use App\Models\Collar;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirebaseTelemetry
{
    public function enabled(): bool
    {
        return (bool) config('firebase.enabled');
    }

    private function credentials(): array
    {
        $path = config('firebase.credentials');
        if (!$path || !is_readable($path)) throw new RuntimeException('Firebase service-account file is not configured.');
        $key = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (empty($key['private_key']) || empty($key['client_email']) || ($key['project_id'] ?? null) !== config('firebase.client.projectId')) {
            throw new RuntimeException('Firebase service-account project does not match the configured project.');
        }
        return $key;
    }

    private function sign(array $claims, array $key): string
    {
        $encode = fn ($value) => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        $body = $encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])).'.'.$encode(json_encode($claims));
        if (!openssl_sign($body, $signature, $key['private_key'], OPENSSL_ALGO_SHA256)) throw new RuntimeException('Could not sign Firebase token.');
        return $body.'.'.$encode($signature);
    }

    private function accessToken(): string
    {
        $key = $this->credentials();
        return Cache::remember('firebase-oauth-'.sha1($key['client_email']), 3000, function () use ($key) {
            $jwt = $this->sign([
                'iss' => $key['client_email'], 'iat' => time(), 'exp' => time() + 3600,
                'aud' => 'https://oauth2.googleapis.com/token',
                'scope' => 'https://www.googleapis.com/auth/firebase.database https://www.googleapis.com/auth/userinfo.email',
            ], $key);
            $response = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt,
            ]);
            if (!$response->successful() || !$response->json('access_token')) throw new RuntimeException('Firebase server authentication failed.');
            return $response->json('access_token');
        });
    }

    protected function write(string $path, array $data): void
    {
        $url = rtrim(config('firebase.client.databaseURL') ?? '', '/');
        if (!preg_match('~^https://[a-z0-9.-]+\.(firebasedatabase\.app|firebaseio\.com)$~', $url)) throw new RuntimeException('Invalid Firebase database URL.');
        $response = Http::withToken($this->accessToken())->timeout(10)->put($url.'/'.$path.'.json', $data);
        if (!$response->successful()) throw new RuntimeException('Firebase database write failed.');
    }

    public function session(User $user): array
    {
        $key = $this->credentials();
        $farm = config('firebase.farm_id');
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $farm)) throw new RuntimeException('Invalid Firebase farm ID.');
        $uid = 'laravel-'.$user->id;
        // A grant expires unless the client still has a valid Laravel session.
        $this->write('access/'.$uid.'/'.$farm, ['expires_at' => (time() + 900) * 1000]);
        return [
            'enabled' => true, 'config' => config('firebase.client'), 'path' => 'farms/'.$farm.'/telemetry',
            'token' => $this->sign([
                'iss' => $key['client_email'], 'sub' => $key['client_email'],
                'aud' => 'https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit',
                'iat' => time(), 'exp' => time() + 3600, 'uid' => $uid,
            ], $key),
        ];
    }

    public function publish(Collar $collar): void
    {
        if (!$this->enabled()) return;
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', (string) config('firebase.farm_id'))) {
            throw new RuntimeException('Invalid Firebase farm ID.');
        }
        $goat = $collar->goat;
        $this->write('farms/'.config('firebase.farm_id').'/telemetry/'.$collar->id, [
            'collar_id' => $collar->id, 'goat_id' => $goat?->id,
            'temperature' => $goat?->temperature !== null ? (float) $goat->temperature : null,
            'movement' => $goat?->movement, 'battery_level' => $collar->battery_level,
            'status' => $goat?->status,
            'recommendation' => VetAdvisoryService::temperatureRecommendation($goat?->temperature),
            'received_at' => $collar->last_seen?->toIso8601String(),
        ]);
    }
}
