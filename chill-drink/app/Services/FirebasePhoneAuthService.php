<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FirebasePhoneAuthService
{
    public function verifyIdToken(string $idToken): object
    {
        $projectId = (string) config('services.firebase.phone_auth.project_id');

        if ($projectId === '') {
            throw new \RuntimeException('Firebase project id is not configured.');
        }

        $oldLeeway = JWT::$leeway;
        JWT::$leeway = 60;

        try {
            $payload = JWT::decode($idToken, $this->firebasePublicKeys());
        } finally {
            JWT::$leeway = $oldLeeway;
        }

        $expectedIssuer = 'https://securetoken.google.com/'.$projectId;

        if (($payload->aud ?? null) !== $projectId || ($payload->iss ?? null) !== $expectedIssuer) {
            throw new \UnexpectedValueException('Firebase token issuer or audience is invalid.');
        }

        if (blank($payload->sub ?? '') || strlen((string) $payload->sub) > 128) {
            throw new \UnexpectedValueException('Firebase token subject is invalid.');
        }

        return $payload;
    }

    /**
     * Verify that the Firebase token belongs to the given phone number.
     *
     * @return array{firebase_user:object,international:string,local:string}
     */
    public function verifyPhoneTokenMatches(string $idToken, string $expectedPhone): array
    {
        $firebaseUser = $this->verifyIdToken($idToken);
        $verifiedPhone = (string) ($firebaseUser->phone_number ?? '');

        if ($verifiedPhone === '') {
            throw new \RuntimeException('Tài khoản Firebase chưa có số điện thoại đã xác minh.');
        }

        $expectedIntl = $this->formatInternationalPhone($expectedPhone);
        $verifiedIntl = $this->formatInternationalPhone($verifiedPhone);

        if ($expectedIntl !== $verifiedIntl) {
            throw new \UnexpectedValueException('Số điện thoại không khớp với mã OTP đã xác minh.');
        }

        return [
            'firebase_user' => $firebaseUser,
            'international' => $verifiedIntl,
            'local' => $this->formatLocalPhone($verifiedPhone),
        ];
    }

    public function makePhoneEmail(string $phoneLocal): string
    {
        $email = $phoneLocal.'@chilldrink.local';

        if (! User::where('email', $email)->exists()) {
            return $email;
        }

        do {
            $email = 'phone_'.Str::random(10).'@chilldrink.local';
        } while (User::where('email', $email)->exists());

        return $email;
    }

    public function formatInternationalPhone(string $phone): string
    {
        $cleaned = preg_replace('/[^\d+]/', '', $phone) ?: '';

        if (str_starts_with($cleaned, '0')) {
            return '+84'.substr($cleaned, 1);
        }

        if (str_starts_with($cleaned, '84')) {
            return '+'.$cleaned;
        }

        if (! str_starts_with($cleaned, '+')) {
            return '+'.$cleaned;
        }

        return $cleaned;
    }

    public function formatLocalPhone(string $phone): string
    {
        $cleaned = preg_replace('/\D/', '', $phone) ?: '';

        if (str_starts_with($cleaned, '84')) {
            return '0'.substr($cleaned, 2);
        }

        if (! str_starts_with($cleaned, '0')) {
            return '0'.$cleaned;
        }

        return $cleaned;
    }

    /**
     * @return array<string, Key>
     */
    private function firebasePublicKeys(): array
    {
        $certificates = Cache::remember('firebase_phone_auth_certificates', now()->addHour(), function (): array {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get('https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com');

            if (! $response->successful() || ! is_array($response->json())) {
                throw new \RuntimeException('Unable to fetch Firebase public certificates.');
            }

            return $response->json();
        });

        $keys = [];

        foreach ($certificates as $keyId => $certificate) {
            if (is_string($keyId) && is_string($certificate) && $certificate !== '') {
                $keys[$keyId] = new Key($certificate, 'RS256');
            }
        }

        if ($keys === []) {
            throw new \RuntimeException('Firebase public certificates are empty.');
        }

        return $keys;
    }
}
