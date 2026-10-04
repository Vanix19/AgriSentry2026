<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeploymentErrors
{
    public static function log(string $message, Throwable $error, array $context = []): void
    {
        // QueryException embeds SQL bindings (including passwords and OTP hashes).
        $source = $error instanceof QueryException ? ($error->getPrevious() ?? $error) : $error;
        $detail = $source->getMessage();
        foreach (['app.key', 'services.semaphore.key', 'mail.mailers.smtp.password',
            'mail.mailers.smtp.username', 'database.connections.mysql.password',
            'database.connections.mysql.username', 'services.gemini.key'] as $name) {
            $secret = config($name);
            if (is_string($secret) && $secret !== '') $detail = str_replace([$secret, base64_encode($secret)], '[redacted]', $detail);
        }
        if (app()->bound('request')) {
            foreach (['password', 'password_confirmation', 'current_password', 'otp', 'reset_token', 'username'] as $name) {
                $value = request()->input($name);
                if (is_string($value) && $value !== '') $detail = str_replace($value, '[redacted]', $detail);
            }
        }
        // Provider exceptions can include SMTP AUTH payloads, URLs, or request bodies.
        $detail = preg_replace('/(?:https?:\/\/\S+|Bearer\s+\S+|AUTH\s+[^\r\n]+|"[^"\r\n]*"|\x27[^\x27\r\n]*\x27)/i', '[redacted]', $detail);
        $detail = preg_replace('/\b(?:apikey|access_token|password|private_key|assertion|code)\b\s*[:=]\s*\S+/i', '[redacted]', $detail);
        Log::error($message, array_merge($context, [
            'type' => get_class($error), 'code' => $source->getCode(),
            'message' => $detail, 'file' => $error->getFile(), 'line' => $error->getLine(),
        ]));
    }
}
