<?php

namespace App\Support;

use Throwable;

class ErrorSanitizer
{
    /**
     * Sanitize error message to display only the clean, concise user-friendly message
     * without technical traces, JSON dumps, SQL errors, or provider brand names.
     */
    public static function sanitize(string|Throwable|null $error): string
    {
        if ($error instanceof Throwable) {
            $error = $error->getMessage();
        }

        if (empty($error)) {
            return 'Terjadi kendala pada sistem. Silakan coba beberapa saat lagi.';
        }

        $message = trim((string) $error);

        // 1. Check if string is JSON or contains JSON payload
        if (str_starts_with($message, '{') || str_contains($message, '{"')) {
            $jsonStart = strpos($message, '{');
            $jsonEnd = strrpos($message, '}');
            if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd > $jsonStart) {
                $jsonStr = substr($message, $jsonStart, $jsonEnd - $jsonStart + 1);
                $decoded = json_decode($jsonStr, true);
                if (is_array($decoded)) {
                    if (! empty($decoded['data']['message'])) {
                        $message = (string) $decoded['data']['message'];
                    } elseif (! empty($decoded['message'])) {
                        $message = (string) $decoded['message'];
                    } elseif (! empty($decoded['data']['rc'])) {
                        $message = 'Transaksi ditolak oleh server provider (RC: '.$decoded['data']['rc'].').';
                    }
                }
            }
        }

        // 2. Strip SQL / PDO specific prefixes
        if (str_contains($message, 'SQLSTATE[') || str_contains($message, 'QueryException')) {
            return 'Terjadi kendala pemrosesan basis data. Silakan hubungi customer support.';
        }

        // 3. Strip file paths and stack trace lines (e.g. "in C:\laragon\www\..." or "at App\...")
        if (preg_match('/in\s+[A-Za-z]:\\\\/i', $message) || str_contains($message, 'Stack trace:')) {
            $parts = preg_split('/(\sin\s+[A-Za-z]:\\\\|\s+at\s+[A-Za-z0-9_\\\\]+)/i', $message);
            $message = trim($parts[0] ?? $message);
        }

        // 4. Strip HTTP client wrappers (e.g. "HTTP request returned status code 500: ")
        $message = preg_replace('/^HTTP request returned status code \d+:\s*/i', '', $message);
        $message = preg_replace('/^cURL error \d+:\s*/i', '', $message);
        $message = preg_replace('/^Connection timed out.*?:\s*/i', 'Koneksi ke server provider terputus: ', $message);

        // 5. Hide / replace any mention of "Digiflazz" (case-insensitive)
        $message = preg_replace('/digiflazz/i', 'Provider', $message);

        // 6. Clean up double spaces or trailing punctuation
        $message = preg_replace('/\s+/', ' ', $message);
        $message = trim($message);

        // 7. Ensure fallback if cleaned message becomes empty or overly cryptic
        if (empty($message) || strlen($message) < 3) {
            return 'Terjadi kendala pada layanan provider. Silakan coba beberapa saat lagi.';
        }

        return $message;
    }
}
